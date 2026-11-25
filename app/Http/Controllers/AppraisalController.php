<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use Modules\Performance\Models\AppraisalCycle;
use Modules\Performance\Models\AppraisalTemplate;
use Modules\Performance\Models\AppraisalTemplateCriterion;
use Modules\Performance\Models\Appraisal;
use Modules\Performance\Models\AppraisalCriteriaScore;
use Modules\Performance\Models\AppraisalChangeRequest;
use Modules\Performance\Models\AppraisalLetter;
use App\Models\HrNotification;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppraisalController extends Controller
{
    /**
     * Helper to get active user, employee, and role.
     */
    protected function getActorContext(): array
    {
        $user = auth()->user();
        $employee = $user ? (Employee::where('user_id', $user->id)->first() ?? Employee::first()) : Employee::first();

        $sessionRole = session('current_role');
        if ($sessionRole) {
            $role = match($sessionRole) {
                'HR Lead' => 'HR Lead',
                'Manager (Team Approvals)', 'HOD / Manager', 'Manager' => 'Manager',
                'Employee' => 'Employee',
                default => 'Super Admin',
            };
        } else {
            $role = match($employee?->system_role ?? '') {
                'HR Lead' => 'HR Lead',
                'Manager (Team Approvals)', 'HOD / Manager', 'Manager' => 'Manager',
                'Employee' => 'Employee',
                default => 'Super Admin',
            };
        }

        return [$user, $employee, $role];
    }

    /**
     * Appraisal Main Hub / Dashboard
     */
    public function index(Request $request)
    {
        [$user, $employee, $role] = $this->getActorContext();

        // Both Review Quarters for the Financial Year
        $financialYear = 'FY 2026/2027';
        $fyCycles = AppraisalCycle::where('status', '!=', 'Archived')
            ->whereNotNull('financial_year')
            ->whereIn('cycle_type', ['mid_year', 'year_end'])
            ->orderBy('start_date', 'asc')
            ->get();

        if ($fyCycles->isEmpty()) {
            $fyCycles = AppraisalCycle::where('status', '!=', 'Archived')
                ->whereIn('cycle_type', ['mid_year', 'year_end'])
                ->orderBy('start_date', 'asc')
                ->take(2)
                ->get();
        }

        // Only initialize appraisals for Active cycles
        foreach ($fyCycles->where('status', 'Active') as $ac) {
            self::initializeAppraisalsForActiveEmployees($ac);
        }

        $cycles = AppraisalCycle::where('status', '!=', 'Archived')->whereNotNull('financial_year')->orderBy('id', 'asc')->get();
        if ($cycles->isEmpty()) {
            $cycles = AppraisalCycle::orderBy('id', 'desc')->get();
        }
        $departments = Department::orderBy('name')->get();
        $templates = AppraisalTemplate::with('criteria')->orderBy('title')->get();

        // Selected cycle resolution
        $cycleId = $request->get('cycle_id');
        $activeCycle = $cycleId ? AppraisalCycle::find($cycleId) : null;
        if (!$activeCycle) {
            $activeCycle = $fyCycles->firstWhere('status', 'Active')
                ?? $fyCycles->firstWhere('cycle_type', 'mid_year')
                ?? $fyCycles->first();
        }

        // Security check: Only Super Admin & HR Lead can view/select an inactive (Upcoming) cycle
        if ($activeCycle && $activeCycle->status !== 'Active' && !in_array($role, ['Super Admin', 'HR Lead'])) {
            $activeCycle = $fyCycles->firstWhere('status', 'Active')
                ?? $fyCycles->firstWhere('cycle_type', 'mid_year')
                ?? $fyCycles->first();
        }

        // Compute Cards Data for Both Quarters
        $cycleCardsData = [];
        foreach ($fyCycles as $cItem) {
            $cQuery = Appraisal::where('cycle_id', $cItem->id);
            if ($role === 'Employee') {
                $cQuery->where('employee_id', $employee?->id);
            } elseif ($role === 'Manager') {
                $empId = $employee?->id;
                $deptId = $employee?->department_id;
                $cQuery->where(function ($q) use ($empId, $deptId) {
                    $q->where('reporting_manager_id', $empId)
                      ->orWhere('hod_id', $empId)
                      ->orWhere('employee_id', $empId)
                      ->orWhereHas('employee', function ($eq) use ($deptId) {
                          $eq->where('department_id', $deptId);
                      });
                });
            }

            $cAppraisals = $cQuery->get();
            $cTotal = $cAppraisals->count();
            $cCompleted = $cAppraisals->where('status', 'completed')->count();
            $cPendingSelf = $cAppraisals->where('status', 'pending_self_eval')->count();
            $cSelfSubmitted = $cTotal - $cPendingSelf;
            $cSelfRate = $cTotal > 0 ? round(($cSelfSubmitted / $cTotal) * 100) : 0;
            $cManagerDone = $cAppraisals->whereNotIn('status', ['pending_self_eval', 'submitted_self_eval', 'pending_manager_eval'])->count();
            $cRate = $cTotal > 0 ? round(($cCompleted / $cTotal) * 100) : 0;

            $cycleCardsData[] = [
                'cycle' => $cItem,
                'is_selected' => ($activeCycle && $activeCycle->id === $cItem->id),
                'is_active_cycle' => ($cItem->status === 'Active'),
                'status' => $cItem->status,
                'total' => $cTotal,
                'completed' => $cCompleted,
                'pending_self' => $cPendingSelf,
                'self_submitted' => $cSelfSubmitted,
                'self_rate' => $cSelfRate,
                'manager_done' => $cManagerDone,
                'rate' => $cRate,
                'quarter_badge' => $cItem->isMidYear() ? 'Quarter 1 • Mid-Year' : 'Quarter 2 • Year-End',
                'quarter_title' => $cItem->isMidYear() ? 'Mid-Year Review' : 'Year-End Review',
                'period_label' => $cItem->performance_period ?: ($cItem->isMidYear() ? 'April to September' : 'October to March'),
            ];
        }

        $query = Appraisal::with([
            'cycle',
            'template',
            'employee.user',
            'employee.department',
            'employee.designation',
            'reportingManager.user',
            'hod.user',
            'criteriaScores',
            'changeRequests'
        ]);

        if ($activeCycle) {
            $query->where('cycle_id', $activeCycle->id);
        }

        // Apply Role Restrictions
        if ($role === 'Employee') {
            // Employee only sees their own appraisals
            $query->where('employee_id', $employee?->id);
        } elseif ($role === 'Manager') {
            // Manager / HOD:
            // Sees appraisals where they are reporting manager OR where they are HOD OR direct subordinates
            $empId = $employee?->id;
            $deptId = $employee?->department_id;

            $query->where(function ($q) use ($empId, $deptId) {
                $q->where('reporting_manager_id', $empId)
                  ->orWhere('hod_id', $empId)
                  ->orWhere('employee_id', $empId)
                  ->orWhereHas('employee', function ($eq) use ($deptId) {
                      $eq->where('department_id', $deptId);
                  });
            });
        }
        // Super Admin & HR Lead have full company access

        // Optional UI Filters
        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->whereHas('employee.user', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            })->orWhereHas('employee', function ($q) use ($term) {
                $q->where('employee_id_number', 'like', "%{$term}%");
            });
        }

        $appraisals = $query->latest()->get();

        // Compute Statistics
        $totalAppraisals = $appraisals->count();
        $completedCount = $appraisals->where('status', 'completed')->count();
        $pendingSelfCount = $appraisals->where('status', 'pending_self_eval')->count();
        $pendingReviewCount = $appraisals->whereIn('status', ['submitted_self_eval', 'pending_manager_eval', 'submitted_manager_eval', 'pending_hod_eval'])->count();
        
        $completedAppraisals = $appraisals->where('status', 'completed');
        $averageScore = $completedAppraisals->count() > 0 
            ? round($completedAppraisals->avg('total_weighted_score'), 2) 
            : 0.00;

        $completionRate = $totalAppraisals > 0 
            ? round(($completedCount / $totalAppraisals) * 100) 
            : 0;

        // Band counts
        $bandCounts = [
            'Exceptional' => $completedAppraisals->where('performance_band', 'Exceptional')->count(),
            'Exceeds Expectations' => $completedAppraisals->where('performance_band', 'Exceeds Expectations')->count(),
            'Meets Expectations' => $completedAppraisals->where('performance_band', 'Meets Expectations')->count(),
            'Needs Improvement' => $completedAppraisals->where('performance_band', 'Needs Improvement')->count(),
            'Unsatisfactory' => $completedAppraisals->where('performance_band', 'Unsatisfactory')->count(),
        ];

        // Pending change requests count
        $pendingChangeRequestsCount = AppraisalChangeRequest::where('status', 'Pending')->count();

        // My submitted change requests (for employee view)
        $myChangeRequests = collect();
        if ($employee || $user) {
            $myChangeRequests = AppraisalChangeRequest::with(['appraisal.cycle', 'requestedBy', 'actionedBy'])
                ->where(function ($q) use ($user, $employee) {
                    if ($user) {
                        $q->where('requested_by_user_id', $user->id);
                    }
                    if ($employee) {
                        $q->orWhereHas('appraisal', fn($aq) => $aq->where('employee_id', $employee->id));
                    }
                })
                ->latest()
                ->get();
        }
        $myChangeRequestsCount = $myChangeRequests->count();

        // User's own letters (if employee) or all letters (if HR/Admin)
        $lettersQuery = AppraisalLetter::with(['employee.user', 'appraisal.cycle'])->latest();
        if ($role === 'Employee') {
            $lettersQuery->where('employee_id', $employee?->id);
        } elseif ($role === 'Manager') {
            $lettersQuery->where(function ($lq) use ($employee) {
                $lq->where('employee_id', $employee?->id)
                   ->orWhereHas('employee', fn($eq) => $eq->where('department_id', $employee?->department_id));
            });
        }
        $letters = $lettersQuery->get();

        // Self-evaluation appraisals for current employee
        $myAppraisals = collect();
        if ($employee) {
            $myAppraisals = Appraisal::with([
                'cycle',
                'template',
                'employee.user',
                'employee.department',
                'employee.designation',
                'reportingManager.user',
                'hod.user',
                'criteriaScores',
                'changeRequests'
            ])
            ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->where('employee_id', $employee->id)
            ->latest()
            ->get();
        }

        return view('pages.appraisal', compact(
            'appraisals',
            'myAppraisals',
            'activeCycle',
            'cycleCardsData',
            'cycles',
            'departments',
            'templates',
            'role',
            'employee',
            'totalAppraisals',
            'completedCount',
            'pendingSelfCount',
            'pendingReviewCount',
            'averageScore',
            'completionRate',
            'bandCounts',
            'pendingChangeRequestsCount',
            'myChangeRequests',
            'myChangeRequestsCount',
            'letters'
        ));
    }

    /**
     * View Detailed Appraisal Form
     */
    public function show($id)
    {
        [$user, $employee, $role] = $this->getActorContext();

        $appraisal = Appraisal::with([
            'cycle',
            'template.criteria',
            'employee.user',
            'employee.department',
            'employee.designation',
            'reportingManager.user',
            'reportingManager.designation',
            'hod.user',
            'hod.designation',
            'newDesignation',
            'criteriaScores',
            'changeRequests.requestedBy',
            'changeRequests.actionedBy',
            'letters'
        ])->findOrFail($id);

        $isEmployeeHimself = ($employee && $appraisal->employee_id == $employee->id);
        $isReportingManager = ($employee && $appraisal->reporting_manager_id == $employee->id);
        $isHod = ($employee && ($appraisal->hod_id == $employee->id || ($employee->department_id == $appraisal->employee?->department_id && in_array($role, ['Manager', 'HR Lead', 'Super Admin']))));
        $isAdminOrHr = in_array($role, ['Super Admin', 'HR Lead']);
        $isEmployeeOnly = ($role === 'Employee') || (!$isAdminOrHr && !$isReportingManager && !$isHod);

        // Authorization check
        if (!$isAdminOrHr && !$isHod && !$isReportingManager && !$isEmployeeHimself) {
            abort(403, 'Unauthorized access to this appraisal record.');
        }

        // Security check: only HR or Admin can view an appraisal from an inactive/upcoming cycle
        if ($appraisal->cycle && $appraisal->cycle->status !== 'Active' && !$isAdminOrHr) {
            abort(403, 'This appraisal review cycle has not been activated yet. Activating review cycles can only be done by HR or Admin.');
        }

        // Deadline & Lock evaluation
        $isEmployeeSubmissionLocked = $appraisal->isEmployeeSubmissionLocked();
        $isManagerReviewLocked = $appraisal->isManagerReviewLocked();

        $isEmployeeOverdue = ($appraisal->status === 'pending_self_eval') && $appraisal->cycle?->self_eval_deadline && now()->gt($appraisal->cycle->self_eval_deadline->endOfDay()) && !$appraisal->is_override_unlocked;
        $isManagerOverdue = in_array($appraisal->status, ['submitted_self_eval', 'pending_manager_eval']) && $appraisal->cycle?->manager_eval_deadline && now()->gt($appraisal->cycle->manager_eval_deadline->endOfDay()) && !$appraisal->is_override_unlocked;

        // Action permissions (Admin has ALL access and is never blocked by deadlines)
        $canEditSelf = $isAdminOrHr || ($isEmployeeHimself && !$isEmployeeSubmissionLocked);
        $canEditManager = $isAdminOrHr || ($isReportingManager && !$isManagerReviewLocked);
        $canEditHod = $isAdminOrHr || ($isHod && !$appraisal->is_locked);

        $canManageIncrement = $isAdminOrHr;
        
        // Increment & Promotion visibility: Admin/HR always; HOD/Employee when toggled visible by HR
        $canSeeIncrement = $isAdminOrHr 
            || ($isHod && $appraisal->is_increment_visible_to_hod)
            || ($isEmployeeHimself && $appraisal->is_increment_visible_to_employee);

        // Anyone locked out by deadline or manual lock can request an unlock
        $canRequestChange = ($isEmployeeHimself && ($isEmployeeSubmissionLocked || $isEmployeeOverdue || $appraisal->is_locked))
            || ($isReportingManager && ($isManagerReviewLocked || $isManagerOverdue || $appraisal->is_locked))
            || ($isHod && $appraisal->is_locked);
        $canUnlock = $isAdminOrHr;

        $designations = Designation::orderBy('name')->get()->unique('name');

        return view('pages.appraisal-show', compact(
            'appraisal',
            'role',
            'employee',
            'isEmployeeHimself',
            'isReportingManager',
            'isHod',
            'isAdminOrHr',
            'isEmployeeOnly',
            'canEditSelf',
            'canEditManager',
            'canEditHod',
            'canManageIncrement',
            'canSeeIncrement',
            'canRequestChange',
            'canUnlock',
            'isEmployeeOverdue',
            'isManagerOverdue',
            'designations'
        ));
    }

    /**
     * Save Draft Evaluation Scores
     */
    public function saveDraft(Request $request, $id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        $appraisal = Appraisal::with('criteriaScores')->findOrFail($id);
        $isAdminOrHr = in_array($role, ['Super Admin', 'HR Lead']);
        $isEmployeeHimself = ($employee && $appraisal->employee_id == $employee->id);
        $isReportingManager = ($employee && $appraisal->reporting_manager_id == $employee->id);

        // Admin has all access and can always save drafts
        if (!$isAdminOrHr) {
            if ($appraisal->cycle && $appraisal->cycle->status !== 'Active') {
                return back()->with('error', 'This review cycle has not been activated by HR yet.');
            }
            if ($isEmployeeHimself && $appraisal->isEmployeeSubmissionLocked()) {
                return back()->with('error', 'Self-evaluation submission duration has passed or is locked. Please request an unlock from HR.');
            }
            if ($isReportingManager && $appraisal->isManagerReviewLocked()) {
                return back()->with('error', 'Manager review duration has passed or is locked. Please request an unlock from HR.');
            }
            if ($appraisal->is_locked && !$appraisal->is_override_unlocked) {
                return back()->with('error', 'Appraisal is locked and cannot be edited.');
            }
        }

        $scoresData = $request->input('scores', []);
        foreach ($scoresData as $scoreId => $val) {
            $scoreItem = AppraisalCriteriaScore::where('appraisal_id', $appraisal->id)->find($scoreId);
            if ($scoreItem) {
                if (isset($val['self_rating'])) {
                    $scoreItem->self_rating = $val['self_rating'] ?: null;
                }
                if (isset($val['self_comments'])) {
                    $scoreItem->self_comments = $val['self_comments'];
                }
                if (isset($val['manager_rating'])) {
                    $scoreItem->manager_rating = $val['manager_rating'] ?: null;
                    $scoreItem->final_rating = $scoreItem->manager_rating;
                }
                if (isset($val['manager_comments'])) {
                    $scoreItem->manager_comments = $val['manager_comments'];
                }
                $scoreItem->save();
            }
        }

        if ($request->has('general_employee_comments')) {
            $appraisal->general_employee_comments = $request->general_employee_comments;
        }
        if ($request->has('general_manager_comments')) {
            $appraisal->general_manager_comments = $request->general_manager_comments;
        }
        if ($request->has('general_hod_comments')) {
            $appraisal->general_hod_comments = $request->general_hod_comments;
        }

        $appraisal->recalculateScore();

        return back()->with('success', 'Draft evaluation scores saved successfully.');
    }

    /**
     * Submit Employee Self-Evaluation
     */
    public function submitSelfEvaluation(Request $request, $id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        $appraisal = Appraisal::with(['criteriaScores', 'employee.user', 'reportingManager.user', 'hod.user', 'cycle'])->findOrFail($id);
        $isAdminOrHr = in_array($role, ['Super Admin', 'HR Lead']);

        if ($appraisal->cycle && $appraisal->cycle->status !== 'Active' && !$isAdminOrHr) {
            return back()->with('error', 'This review cycle has not been activated by HR yet.');
        }

        if (!$isAdminOrHr && $appraisal->isEmployeeSubmissionLocked()) {
            return back()->with('error', 'The self-evaluation deadline has passed. This appraisal is locked. Please submit an unlock request to HR with your reason.');
        }

        // Save scores first
        $this->saveDraft($request, $id);

        // Validate all criteria have self-rating
        foreach ($appraisal->criteriaScores as $item) {
            if (!$item->self_rating || $item->self_rating < 1 || $item->self_rating > 5) {
                return back()->with('error', "Please provide a self-rating (1–5) for '{$item->criteria_name}'.");
            }
        }

        // Determine next status based on workflow hierarchy
        if ($appraisal->workflow_type === 'three_tier' && $appraisal->reporting_manager_id) {
            $appraisal->status = 'pending_manager_eval';
            $nextReviewer = $appraisal->reportingManager;
            $roleLabel = 'Reporting Manager';
        } else {
            $appraisal->status = 'pending_hod_eval';
            $nextReviewer = $appraisal->hod;
            $roleLabel = 'Head of Department';
        }

        $appraisal->self_submitted_at = now();
        $appraisal->is_override_unlocked = false; // Reset unlock override so manager adheres to manager duration
        $appraisal->save();

        // Notification to next reviewer
        if ($nextReviewer && $nextReviewer->user_id) {
            HrNotification::create([
                'user_id' => $nextReviewer->user_id,
                'type' => 'appraisal_review_required',
                'title' => 'Appraisal Review Required',
                'message' => "{$appraisal->employee?->user?->name} has submitted their self-evaluation for {$appraisal->cycle?->name}. Your review is now required.",
                'link' => route('appraisal.show', $appraisal->id),
                'is_read' => false,
            ]);
        }

        return redirect()->route('appraisal.show', $appraisal->id)
            ->with('success', "Self-evaluation submitted successfully! Form is now routed to your {$roleLabel} for review.");
    }

    /**
     * Submit Reporting Manager Evaluation
     */
    public function submitManagerEvaluation(Request $request, $id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        $appraisal = Appraisal::with(['criteriaScores', 'employee.user', 'hod.user', 'cycle'])->findOrFail($id);
        $isAdminOrHr = in_array($role, ['Super Admin', 'HR Lead']);

        if ($appraisal->cycle && $appraisal->cycle->status !== 'Active' && !$isAdminOrHr) {
            return back()->with('error', 'This review cycle has not been activated by HR yet.');
        }

        if (!$isAdminOrHr && $appraisal->isManagerReviewLocked()) {
            return back()->with('error', 'The manager review deadline has passed. This appraisal is locked. Please submit an unlock request to HR with your reason.');
        }

        $this->saveDraft($request, $id);

        foreach ($appraisal->criteriaScores as $item) {
            if (!$item->manager_rating || $item->manager_rating < 1 || $item->manager_rating > 5) {
                return back()->with('error', "Please provide a manager rating (1–5) for '{$item->criteria_name}'.");
            }
        }

        $appraisal->manager_submitted_at = now();
        $appraisal->recalculateScore();

        // Route to HOD
        $appraisal->status = 'pending_hod_eval';
        $appraisal->is_override_unlocked = false;
        $appraisal->save();

        // Notify HOD
        if ($appraisal->hod && $appraisal->hod->user_id) {
            HrNotification::create([
                'user_id' => $appraisal->hod->user_id,
                'type' => 'appraisal_hod_review',
                'title' => 'HOD Final Appraisal Review Required',
                'message' => "Reporting manager evaluation has been completed for {$appraisal->employee?->user?->name}. Please perform HOD final review and approval.",
                'link' => route('appraisal.show', $appraisal->id),
                'is_read' => false,
            ]);
        }

        return redirect()->route('appraisal.show', $appraisal->id)
            ->with('success', 'Manager evaluation submitted successfully! Forwarded to HOD for final approval.');
    }

    /**
     * Submit HOD Final Evaluation & Complete Appraisal
     */
    public function submitHodEvaluation(Request $request, $id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        $appraisal = Appraisal::with(['criteriaScores', 'employee.user', 'cycle'])->findOrFail($id);
        $isAdminOrHr = in_array($role, ['Super Admin', 'HR Lead']);

        if ($appraisal->cycle && $appraisal->cycle->status !== 'Active' && !$isAdminOrHr) {
            return back()->with('error', 'This review cycle has not been activated by HR yet.');
        }

        $this->saveDraft($request, $id);

        foreach ($appraisal->criteriaScores as $item) {
            if (!$item->manager_rating || $item->manager_rating < 1 || $item->manager_rating > 5) {
                return back()->with('error', "Please ensure all criteria have a final rating (1–5).");
            }
        }

        $appraisal->hod_submitted_at = now();
        $appraisal->status = 'completed';
        $appraisal->is_locked = true;
        $appraisal->recalculateScore();

        // Notify Employee
        if ($appraisal->employee && $appraisal->employee->user_id) {
            HrNotification::create([
                'user_id' => $appraisal->employee->user_id,
                'type' => 'appraisal_completed',
                'title' => 'Performance Appraisal Completed',
                'message' => "Your performance review for {$appraisal->cycle?->name} is finalized with a score of {$appraisal->total_weighted_score} / 5.00 ({$appraisal->performance_band}).",
                'link' => route('appraisal.show', $appraisal->id),
                'is_read' => false,
            ]);
        }

        // Notify HR Admins
        $adminUserIds = User::whereHas('employee', function ($q) {
            $q->whereIn('system_role', ['Super (Admin)', 'HR Lead']);
        })->pluck('id')->toArray();

        foreach ($adminUserIds as $adminId) {
            HrNotification::create([
                'user_id' => $adminId,
                'type' => 'appraisal_finalized',
                'title' => 'Appraisal Ready for Increment Review',
                'message' => "HOD review completed for {$appraisal->employee?->user?->name}. Score: {$appraisal->total_weighted_score} ({$appraisal->performance_band}).",
                'link' => route('appraisal.show', $appraisal->id),
                'is_read' => false,
            ]);
        }

        return redirect()->route('appraisal.show', $appraisal->id)
            ->with('success', "Appraisal successfully finalized! Performance score: {$appraisal->total_weighted_score} / 5.00 ({$appraisal->performance_band}).");
    }

    /**
     * Update Increment & Promotion Data (HR Exclusive)
     */
    public function updateIncrementPromotion(Request $request, $id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        if (!in_array($role, ['Super Admin', 'HR Lead'])) {
            abort(403, 'Only HR Administrators can modify increment and promotion terms.');
        }

        $appraisal = Appraisal::findOrFail($id);

        if ($appraisal->cycle && !$appraisal->cycle->allowsIncrements()) {
            return back()->with('error', 'Salary increments and promotions cannot be awarded during Mid-Year Reviews. Mid-Year reviews cover April to September and focus on progress, areas for improvement, and KPI adjustments. Increments are evaluated during Year-End Reviews.');
        }

        $appraisal->update([
            'increment_percentage' => (float)$request->input('increment_percentage', 0),
            'is_promoted' => $request->boolean('is_promoted'),
            'new_designation_id' => $request->input('new_designation_id') ?: null,
            'effective_date' => $request->input('effective_date') ?: null,
            'is_increment_visible_to_employee' => $request->boolean('is_increment_visible_to_employee'),
            'is_increment_visible_to_hod' => $request->boolean('is_increment_visible_to_hod'),
        ]);

        return back()->with('success', 'Increment & Promotion details updated successfully.');
    }

    /**
     * Submit Change Request (When Appraisal is Locked)
     */
    public function requestChange(Request $request, $id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        $appraisal = Appraisal::findOrFail($id);

        $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        $changeReq = AppraisalChangeRequest::create([
            'appraisal_id' => $appraisal->id,
            'requested_by_user_id' => $user->id,
            'role_at_request' => $role,
            'reason' => $request->reason,
            'status' => 'Pending',
        ]);

        // Notify HR Admins
        $adminUserIds = User::whereHas('employee', function ($q) {
            $q->whereIn('system_role', ['Super (Admin)', 'HR Lead']);
        })->pluck('id')->toArray();

        $empName = $appraisal->employee?->user?->name ?? 'Employee';
        foreach ($adminUserIds as $adminId) {
            HrNotification::create([
                'user_id' => $adminId,
                'type' => 'appraisal_unlock_request',
                'title' => 'Appraisal Unlock Request',
                'message' => "{$user->name} ({$role}) requested an appraisal unlock for {$empName}. Reason: {$request->reason}",
                'link' => route('appraisal.change-requests.index'),
                'is_read' => false,
            ]);
        }

        return back()->with('success', 'Change request submitted to HR. The appraisal will remain locked until HR approves and unlocks it.');
    }

    /**
     * Direct HR Unlock Appraisal
     */
    public function unlockAppraisal(Request $request, $id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        if (!in_array($role, ['Super Admin', 'HR Lead'])) {
            abort(403, 'Only HR Administrators can unlock appraisals.');
        }

        $appraisal = Appraisal::with('employee.user')->findOrFail($id);
        $defaultStage = in_array($appraisal->status, ['submitted_self_eval', 'pending_manager_eval']) 
            ? 'pending_manager_eval' 
            : 'pending_self_eval';
        $targetStage = $request->input('target_stage', $defaultStage);

        $extensionDays = (int)$request->input('extension_days', 7);
        if ($extensionDays < 1) {
            $extensionDays = 7;
        }
        $unlockedUntil = now()->addDays($extensionDays)->endOfDay();

        $appraisal->update([
            'is_locked' => false,
            'status' => $targetStage,
            'is_override_unlocked' => true,
            'unlocked_until' => $unlockedUntil,
            'unlocked_by_user_id' => $user->id,
        ]);

        // Resolve any open change requests
        AppraisalChangeRequest::where('appraisal_id', $appraisal->id)
            ->where('status', 'Pending')
            ->update([
                'status' => 'Approved_Unlocked',
                'actioned_by_user_id' => $user->id,
                'action_notes' => $request->input('notes', "Unlocked by HR Administrator with {$extensionDays}-day extension until {$unlockedUntil->format('d M Y')}"),
                'actioned_at' => now(),
            ]);

        // Notify Employee
        if ($appraisal->employee && $appraisal->employee->user_id) {
            HrNotification::create([
                'user_id' => $appraisal->employee->user_id,
                'type' => 'appraisal_unlocked',
                'title' => 'Appraisal Form Unlocked',
                'message' => "HR has unlocked your appraisal for {$appraisal->cycle?->name} until {$unlockedUntil->format('d M Y')}. You can now make required edits and resubmit.",
                'link' => route('appraisal.show', $appraisal->id),
                'is_read' => false,
            ]);
        }

        return back()->with('success', "Appraisal unlocked successfully until {$unlockedUntil->format('d M Y')}. Status set to '{$targetStage}'.");
    }

    /**
     * Change Requests Management Center
     */
    public function changeRequestsIndex(Request $request)
    {
        [$user, $employee, $role] = $this->getActorContext();

        $query = AppraisalChangeRequest::with([
            'appraisal.employee.user',
            'appraisal.cycle',
            'requestedBy',
            'actionedBy'
        ])->latest();

        if ($role === 'Employee') {
            $query->where(function ($q) use ($user, $employee) {
                if ($user) {
                    $q->where('requested_by_user_id', $user->id);
                }
                if ($employee) {
                    $q->orWhereHas('appraisal', fn($aq) => $aq->where('employee_id', $employee->id));
                }
            });
        } elseif ($role === 'Manager') {
            $deptId = $employee?->department_id;
            $empId = $employee?->id;
            $query->where(function ($q) use ($user, $empId, $deptId) {
                if ($user) {
                    $q->where('requested_by_user_id', $user->id);
                }
                $q->orWhereHas('appraisal', function ($aq) use ($empId, $deptId) {
                    $aq->where('reporting_manager_id', $empId)
                      ->orWhere('hod_id', $empId)
                      ->orWhereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
                });
            });
        }
        // HR Lead & Super Admin see all requests

        $requests = $query->get();

        return view('pages.appraisal-change-requests', compact('requests', 'role', 'employee'));
    }

    /**
     * Action on Change Request (Approve & Unlock OR Reject)
     */
    public function actionChangeRequest(Request $request, $id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        if (!in_array($role, ['Super Admin', 'HR Lead'])) {
            abort(403, 'Unauthorized');
        }

        $changeReq = AppraisalChangeRequest::with('appraisal.employee.user')->findOrFail($id);
        $action = $request->input('action'); // 'approve' or 'reject'
        $notes = $request->input('notes', '');

        if ($action === 'approve') {
            $extensionDays = (int)$request->input('extension_days', 7);
            if ($extensionDays < 1) {
                $extensionDays = 7;
            }
            $unlockedUntil = now()->addDays($extensionDays)->endOfDay();

            $defaultStage = in_array($changeReq->appraisal->status, ['submitted_self_eval', 'pending_manager_eval']) 
                ? 'pending_manager_eval' 
                : 'pending_self_eval';
            $targetStage = $request->input('target_stage', $defaultStage);

            $changeReq->update([
                'status' => 'Approved_Unlocked',
                'actioned_by_user_id' => $user->id,
                'action_notes' => $notes ?: "Approved with {$extensionDays}-day extension until {$unlockedUntil->format('d M Y')}",
                'actioned_at' => now(),
            ]);

            $changeReq->appraisal->update([
                'is_locked' => false,
                'status' => $targetStage,
                'is_override_unlocked' => true,
                'unlocked_until' => $unlockedUntil,
                'unlocked_by_user_id' => $user->id,
            ]);

            if ($changeReq->appraisal->employee && $changeReq->appraisal->employee->user_id) {
                HrNotification::create([
                    'user_id' => $changeReq->appraisal->employee->user_id,
                    'type' => 'change_request_approved',
                    'title' => 'Appraisal Change Request Approved',
                    'message' => "Your change request has been approved by HR. The appraisal form is unlocked until {$unlockedUntil->format('d M Y')} for edits.",
                    'link' => route('appraisal.show', $changeReq->appraisal_id),
                    'is_read' => false,
                ]);
            }

            return back()->with('success', "Change request approved. Appraisal unlocked until {$unlockedUntil->format('d M Y')}.");
        } else {
            $changeReq->update([
                'status' => 'Rejected',
                'actioned_by_user_id' => $user->id,
                'action_notes' => $notes,
                'actioned_at' => now(),
            ]);

            if ($changeReq->requested_by_user_id) {
                HrNotification::create([
                    'user_id' => $changeReq->requested_by_user_id,
                    'type' => 'change_request_rejected',
                    'title' => 'Change Request Declined',
                    'message' => "Your change request for {$changeReq->appraisal?->employee?->user?->name} was reviewed by HR and declined. Note: {$notes}",
                    'link' => route('appraisal.show', $changeReq->appraisal_id),
                    'is_read' => false,
                ]);
            }

            return back()->with('info', 'Change request marked as rejected.');
        }
    }

    /**
     * Template Management View
     */
    public function templatesIndex()
    {
        [$user, $employee, $role] = $this->getActorContext();
        if (!in_array($role, ['Super Admin', 'HR Lead'])) {
            return redirect()->route('appraisal.index')->with('error', 'Template management is restricted to HR.');
        }

        $templates = AppraisalTemplate::with(['criteria', 'designation', 'department'])->get();
        $designations = Designation::orderBy('name')->get()->unique('name');
        $departments = Department::orderBy('name')->get()->unique('name');

        return view('pages.appraisal-templates', compact('templates', 'designations', 'departments', 'role', 'employee'));
    }

    /**
     * Toggle Appraisal Template Active Status (Enables/Disables Template)
     * When enabled, auto-initializes appraisals for matching employees in the active cycle.
     */
    public function toggleTemplate(Request $request, $id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        if (!in_array($role, ['Super Admin', 'HR Lead'])) {
            abort(403, 'Unauthorized');
        }

        $template = AppraisalTemplate::with('criteria')->findOrFail($id);
        $template->is_active = !$template->is_active;
        $template->save();

        $count = 0;
        if ($template->is_active) {
            $count = self::initializeAppraisalsForActiveEmployees(null, $template);
        }

        $state = $template->is_active ? 'enabled' : 'disabled';
        $msg = "Appraisal template '{$template->title}' has been {$state}.";
        if ($count > 0) {
            $msg .= " Initialized {$count} employee appraisal record(s).";
        }

        return back()->with('success', $msg);
    }

    /**
     * Store New Appraisal Template
     */
    public function storeTemplate(Request $request)
    {
        [$user, $employee, $role] = $this->getActorContext();
        if (!in_array($role, ['Super Admin', 'HR Lead'])) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'applicable_type' => 'required|string',
            'criteria' => 'required|array|min:1',
            'criteria.*.name' => 'required|string',
            'criteria.*.weightage' => 'required|numeric|min:1|max:100',
        ]);

        // Validate weightages sum to 100%
        $totalWeightage = array_sum(array_column($request->criteria, 'weightage'));
        if (abs($totalWeightage - 100.00) > 0.01) {
            return back()->withInput()->with('error', "Total weightage must equal exactly 100%. Current sum: {$totalWeightage}%");
        }

        $template = AppraisalTemplate::create([
            'title' => $request->title,
            'applicable_type' => $request->applicable_type,
            'designation_id' => $request->designation_id ?: null,
            'department_id' => $request->department_id ?: null,
            'description' => $request->description,
            'is_active' => true,
        ]);

        foreach ($request->criteria as $idx => $crit) {
            AppraisalTemplateCriterion::create([
                'template_id' => $template->id,
                'name' => $crit['name'],
                'description' => $crit['description'] ?? '',
                'weightage' => (float)$crit['weightage'],
                'sort_order' => $idx + 1,
            ]);
        }

        self::initializeAppraisalsForActiveEmployees(null, $template);

        return back()->with('success', 'New appraisal template created and enabled successfully.');
    }

    /**
     * Update Existing Appraisal Template
     */
    public function updateTemplate(Request $request, $id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        if (!in_array($role, ['Super Admin', 'HR Lead'])) {
            abort(403, 'Unauthorized');
        }

        $template = AppraisalTemplate::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'applicable_type' => 'required|string',
            'criteria' => 'required|array|min:1',
            'criteria.*.name' => 'required|string',
            'criteria.*.weightage' => 'required|numeric|min:1|max:100',
        ]);

        // Validate weightages sum to 100%
        $totalWeightage = array_sum(array_column($request->criteria, 'weightage'));
        if (abs($totalWeightage - 100.00) > 0.01) {
            return back()->withInput()->with('error', "Total weightage must equal exactly 100%. Current sum: {$totalWeightage}%");
        }

        $template->update([
            'title' => $request->title,
            'applicable_type' => $request->applicable_type,
            'designation_id' => $request->designation_id ?: null,
            'department_id' => $request->department_id ?: null,
            'description' => $request->description,
        ]);

        // Re-sync criteria
        $template->criteria()->delete();

        foreach ($request->criteria as $idx => $crit) {
            AppraisalTemplateCriterion::create([
                'template_id' => $template->id,
                'name' => $crit['name'],
                'description' => $crit['description'] ?? '',
                'weightage' => (float)$crit['weightage'],
                'sort_order' => $idx + 1,
            ]);
        }

        if ($template->is_active) {
            self::initializeAppraisalsForActiveEmployees(null, $template);
        }

        return back()->with('success', 'Appraisal template updated successfully.');
    }

    /**
     * Initialize appraisals for active employees when templates are enabled or cycles start.
     */
    public static function initializeAppraisalsForActiveEmployees(?AppraisalCycle $cycle = null, ?AppraisalTemplate $specificTemplate = null): int
    {
        if (!$cycle) {
            $cycle = AppraisalCycle::where('status', 'Active')->latest()->first() ?? AppraisalCycle::latest()->first();
        }

        if (!$cycle) {
            return 0;
        }

        $activeTemplates = AppraisalTemplate::with('criteria')->where('is_active', true)->get();
        if ($activeTemplates->isEmpty()) {
            return 0;
        }

        $employees = Employee::all();
        $initialized = 0;

        foreach ($employees as $emp) {
            $existing = Appraisal::where('cycle_id', $cycle->id)->where('employee_id', $emp->id)->first();
            if ($existing && $existing->criteriaScores()->count() > 0) {
                continue;
            }

            $template = null;
            if ($specificTemplate && $specificTemplate->is_active) {
                if (($specificTemplate->designation_id && $specificTemplate->designation_id == $emp->designation_id)
                    || ($specificTemplate->department_id && $specificTemplate->department_id == $emp->department_id)
                    || (!$specificTemplate->designation_id && !$specificTemplate->department_id)) {
                    $template = $specificTemplate;
                }
            }

            if (!$template) {
                // 1. Designation match
                $template = $activeTemplates->firstWhere('designation_id', $emp->designation_id);
                // 2. Department match
                if (!$template && $emp->department_id) {
                    $template = $activeTemplates->firstWhere('department_id', $emp->department_id);
                }
                // 3. Fallback generic active template
                if (!$template) {
                    $template = $activeTemplates->first(fn($t) => is_null($t->designation_id) && is_null($t->department_id));
                }
                // 4. Any first active template
                if (!$template) {
                    $template = $activeTemplates->first();
                }
            }

            if (!$template || $template->criteria->isEmpty()) {
                continue;
            }

            $workflow = $emp->reporting_person_id ? 'three_tier' : 'two_tier';
            $hod = $emp->hod_id ?: Employee::where('department_id', $emp->department_id)->where('id', '!=', $emp->id)->first()?->id;

            if (!$existing) {
                $existing = Appraisal::create([
                    'cycle_id' => $cycle->id,
                    'employee_id' => $emp->id,
                    'template_id' => $template->id,
                    'reporting_manager_id' => $emp->reporting_person_id,
                    'hod_id' => $hod,
                    'workflow_type' => $workflow,
                    'status' => 'pending_self_eval',
                    'is_locked' => false,
                ]);
                $initialized++;
            } else {
                $existing->update([
                    'template_id' => $template->id,
                    'reporting_manager_id' => $emp->reporting_person_id,
                    'hod_id' => $hod,
                    'workflow_type' => $workflow,
                ]);
            }

            foreach ($template->criteria as $criterion) {
                AppraisalCriteriaScore::firstOrCreate(
                    ['appraisal_id' => $existing->id, 'criteria_name' => $criterion->name],
                    [
                        'criteria_description' => $criterion->description,
                        'weightage' => $criterion->weightage,
                        'self_rating' => null,
                        'manager_rating' => null,
                        'final_rating' => null,
                        'weighted_score' => 0.00,
                    ]
                );
            }
        }

        return $initialized;
    }

    /**
     * Delete Template
     */
    public function destroyTemplate($id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        if (!in_array($role, ['Super Admin', 'HR Lead'])) {
            abort(403, 'Unauthorized');
        }

        $template = AppraisalTemplate::findOrFail($id);
        $template->delete();

        return back()->with('success', 'Appraisal template removed.');
    }

    /**
     * Store New Appraisal Cycle
     */
    public function storeCycle(Request $request)
    {
        [$user, $employee, $role] = $this->getActorContext();
        if (!in_array($role, ['Super Admin', 'HR Lead'])) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'review_period' => 'required|string',
            'cycle_type' => 'nullable|string|in:mid_year,year_end,probation,other',
            'financial_year' => 'nullable|string|max:50',
            'performance_period' => 'nullable|string|max:100',
            'performance_period_start' => 'nullable|date',
            'performance_period_end' => 'nullable|date',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'self_eval_deadline' => 'nullable|date',
            'manager_eval_deadline' => 'nullable|date',
        ]);

        $cycleType = $request->input('cycle_type');
        if (!$cycleType) {
            $periodLower = strtolower($request->review_period);
            if (str_contains($periodLower, 'mid')) {
                $cycleType = 'mid_year';
            } elseif (str_contains($periodLower, 'probation')) {
                $cycleType = 'probation';
            } else {
                $cycleType = 'year_end';
            }
        }

        $allowIncrements = ($cycleType === 'mid_year') ? false : $request->boolean('allow_increments', true);

        // Auto-assign financial year if missing
        $financialYear = $request->financial_year;
        if (!$financialYear) {
            $year = date('Y', strtotime($request->start_date));
            $financialYear = "FY {$year}/" . ($year + 1);
        }

        // Auto-assign performance period description if missing
        $perfPeriod = $request->performance_period;
        if (!$perfPeriod) {
            $perfPeriod = ($cycleType === 'mid_year') ? 'April to September' : 'October to March';
        }

        $focusAreas = $request->focus_areas;
        if (!$focusAreas) {
            $focusAreas = ($cycleType === 'mid_year')
                ? 'Reviewing employee progress, identifying areas for improvement, and setting or adjusting KPIs for the remainder of the year. Excludes salary increments.'
                : 'Final performance evaluation, identifying employees for potential promotions and increments considered alongside approved company budget.';
        }

        $cycle = AppraisalCycle::create([
            'name' => $request->name,
            'review_period' => $request->review_period,
            'cycle_type' => $cycleType,
            'financial_year' => $financialYear,
            'performance_period' => $perfPeriod,
            'performance_period_start' => $request->performance_period_start,
            'performance_period_end' => $request->performance_period_end,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'self_eval_deadline' => $request->self_eval_deadline,
            'manager_eval_deadline' => $request->manager_eval_deadline,
            'allow_increments' => $allowIncrements,
            'status' => 'Active',
            'description' => $request->description,
            'focus_areas' => $focusAreas,
        ]);

        // Auto-initialize appraisals for all active employees if requested
        if ($request->boolean('initialize_all_employees')) {
            $defaultTemplate = AppraisalTemplate::with('criteria')->where('is_active', true)->first();
            if ($defaultTemplate) {
                $employees = Employee::all();
                foreach ($employees as $emp) {
                    // Match template by designation or fallback
                    $matchedTemplate = AppraisalTemplate::with('criteria')
                        ->where('designation_id', $emp->designation_id)
                        ->first() ?? $defaultTemplate;

                    $workflow = $emp->reporting_person_id ? 'three_tier' : 'two_tier';

                    $app = Appraisal::firstOrCreate(
                        ['cycle_id' => $cycle->id, 'employee_id' => $emp->id],
                        [
                            'template_id' => $matchedTemplate->id,
                            'reporting_manager_id' => $emp->reporting_person_id,
                            'hod_id' => $emp->hod_id ?: Employee::where('department_id', $emp->department_id)->where('id', '!=', $emp->id)->first()?->id,
                            'workflow_type' => $workflow,
                            'status' => 'pending_self_eval',
                            'is_locked' => false,
                        ]
                    );

                    foreach ($matchedTemplate->criteria as $criterion) {
                        AppraisalCriteriaScore::firstOrCreate(
                            ['appraisal_id' => $app->id, 'criteria_name' => $criterion->name],
                            [
                                'criteria_description' => $criterion->description,
                                'weightage' => $criterion->weightage,
                                'self_rating' => null,
                                'manager_rating' => null,
                                'final_rating' => null,
                                'weighted_score' => 0.00,
                            ]
                        );
                    }

                    if ($emp->user_id) {
                        HrNotification::create([
                            'user_id' => $emp->user_id,
                            'type' => 'appraisal_cycle_launched',
                            'title' => "Appraisal Cycle Launched: {$cycle->name}",
                            'message' => "The performance appraisal cycle is now open. Please complete and submit your self-evaluation before " . ($cycle->self_eval_deadline?->format('d M Y') ?? 'the deadline') . ".",
                            'link' => route('appraisal.show', $app->id),
                            'is_read' => false,
                        ]);
                    }
                }
            }
        }

        return back()->with('success', "Appraisal cycle '{$cycle->name}' created successfully.");
    }

    /**
     * Toggle Appraisal Cycle Activation Status (HR Lead & Super Admin only)
     */
    public function toggleCycleStatus($id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        if (!in_array($role, ['Super Admin', 'HR Lead'])) {
            abort(403, 'Unauthorized. Activating or managing appraisal cycles can only be done by HR or Admin.');
        }

        $cycle = AppraisalCycle::findOrFail($id);
        $newStatus = ($cycle->status === 'Active') ? 'Upcoming' : 'Active';
        $cycle->update(['status' => $newStatus]);

        if ($newStatus === 'Active') {
            self::initializeAppraisalsForActiveEmployees($cycle);
            return back()->with('success', "Appraisal cycle '{$cycle->name}' has been activated successfully by HR/Admin.");
        }

        return back()->with('success', "Appraisal cycle '{$cycle->name}' is now set to Upcoming.");
    }

    /**
     * Generate Official Letter (Increment, Promotion, Warning, Confirmation)
     */
    public function generateLetter(Request $request, $id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        if (!in_array($role, ['Super Admin', 'HR Lead'])) {
            abort(403, 'Unauthorized');
        }

        $appraisal = Appraisal::with(['employee.user', 'employee.designation', 'employee.department', 'cycle', 'newDesignation'])->findOrFail($id);

        $request->validate([
            'letter_type' => 'required|in:increment,promotion,warning,confirmation',
            'signatory_name' => 'required|string',
        ]);

        $type = $request->letter_type;

        if ($appraisal->cycle && !$appraisal->cycle->allowsIncrements() && in_array($type, ['increment', 'promotion'])) {
            return back()->with('error', 'Salary increment and promotion letters cannot be issued for Mid-Year Reviews. Mid-Year reviews focus on progress and KPI adjustments; increments are evaluated in Year-End Reviews.');
        }
        $empName = $appraisal->employee?->user?->name ?? 'Employee';
        $desig = $appraisal->employee?->designation?->name ?? 'Staff';
        $dept = $appraisal->employee?->department?->name ?? 'General';
        $refNo = 'LTR/' . date('Y') . '/' . strtoupper(substr($type, 0, 3)) . '-' . str_pad($appraisal->id, 3, '0', STR_PAD_LEFT);

        $titles = [
            'increment' => "Annual Performance Increment Letter - {$appraisal->cycle?->name}",
            'promotion' => "Notice of Promotion & Role Reclassification - {$appraisal->cycle?->name}",
            'warning' => "Formal Performance Advisory Notice - {$appraisal->cycle?->name}",
            'confirmation' => "Employment Confirmation & Successful Review - {$appraisal->cycle?->name}",
        ];

        $title = $titles[$type] ?? 'Official Performance Letter';

        // Pre-draft default content
        $content = match($type) {
            'increment' => "<p>Dear {$empName},</p>
                <p>We are pleased to inform you that in recognition of your notable achievements and contributions during the <strong>{$appraisal->cycle?->name}</strong> (Performance Band: <strong>{$appraisal->performance_band}</strong>, Final Score: <strong>{$appraisal->total_weighted_score} / 5.00</strong>), the Executive Management has approved a salary increment of <strong>{$appraisal->increment_percentage}%</strong>.</p>
                <p>This increment will take effect on <strong>" . ($appraisal->effective_date?->format('d F Y') ?? now()->addMonth()->format('01 F Y')) . "</strong>.</p>
                <p>We thank you for your ongoing commitment and look forward to your continued impact in our organization.</p>",

            'promotion' => "<p>Dear {$empName},</p>
                <p>Following the successful evaluation of your performance in the <strong>{$appraisal->cycle?->name}</strong>, we are thrilled to formally announce your promotion to the role of <strong>" . ($appraisal->newDesignation?->name ?? 'Senior Executive') . "</strong> effective from <strong>" . ($appraisal->effective_date?->format('d F Y') ?? now()->format('d F Y')) . "</strong>.</p>
                <p>Your leadership potential and dedicated execution have positioned you as an essential asset to the {$dept} team.</p>",

            'warning' => "<p>Dear {$empName},</p>
                <p>This letter serves as a formal follow-up to your performance evaluation in the <strong>{$appraisal->cycle?->name}</strong>, where your final score was evaluated as <strong>{$appraisal->total_weighted_score} / 5.00</strong> (Band: <strong>{$appraisal->performance_band}</strong>).</p>
                <p>A Performance Improvement Plan (PIP) has been initiated in coordination with your Head of Department. Your progress will be actively monitored over the next 60 days.</p>",

            'confirmation' => "<p>Dear {$empName},</p>
                <p>We take great pleasure in confirming your permanent employment status with Loops HR as <strong>{$desig}</strong>, following the successful completion of your probationary evaluation period.</p>
                <p>All permanent employment terms and enterprise benefits are effective immediately.</p>",
        };

        $letter = AppraisalLetter::create([
            'appraisal_id' => $appraisal->id,
            'employee_id' => $appraisal->employee_id,
            'letter_type' => $type,
            'reference_no' => $refNo,
            'title' => $title,
            'template_content' => $content,
            'status' => 'pending_signatory',
            'signatory_name' => $request->signatory_name,
        ]);

        return back()->with('success', "Letter '{$refNo}' generated and queued for authorized signatory e-signature.");
    }

    /**
     * E-Sign Letter by Authorized Signatory (Anoj / Shehan)
     */
    public function signLetter(Request $request, $id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        $letter = AppraisalLetter::with(['employee.user'])->findOrFail($id);

        $request->validate([
            'signature_data' => 'required|string',
        ]);

        $letter->update([
            'status' => 'signed_by_management',
            'signatory_user_id' => $user->id,
            'signatory_signed_at' => now(),
            'signatory_signature_data' => $request->signature_data,
        ]);

        // Notify employee to acknowledge
        if ($letter->employee && $letter->employee->user_id) {
            HrNotification::create([
                'user_id' => $letter->employee->user_id,
                'type' => 'letter_ready_for_acknowledgment',
                'title' => "Official Letter Ready: {$letter->title}",
                'message' => "Your {$letter->letter_type} letter has been digitally signed by management. Please view and submit your acknowledgment e-signature.",
                'link' => route('appraisal.show', $letter->appraisal_id ?? 1),
                'is_read' => false,
            ]);
        }

        return back()->with('success', 'Letter successfully signed by authorized signatory and dispatched to employee for acknowledgment.');
    }

    /**
     * Employee Acknowledges Letter with E-Signature
     */
    public function acknowledgeLetter(Request $request, $id)
    {
        [$user, $employee, $role] = $this->getActorContext();
        $letter = AppraisalLetter::findOrFail($id);

        $request->validate([
            'signature_data' => 'required|string',
        ]);

        $letter->update([
            'status' => 'acknowledged_by_employee',
            'employee_acknowledged_at' => now(),
            'employee_signature_data' => $request->signature_data,
        ]);

        return back()->with('success', 'You have acknowledged and digitally signed this official letter. You may now download your PDF copy.');
    }

    /**
     * Download Letter as Official PDF
     */
    public function downloadLetterPdf($id)
    {
        $letter = AppraisalLetter::with([
            'appraisal.cycle',
            'employee.user',
            'employee.designation',
            'employee.department',
            'signatoryUser'
        ])->findOrFail($id);

        $pdf = Pdf::loadView('reports.pdf-appraisal-letter', [
            'letter' => $letter,
            'employee' => $letter->employee,
            'user' => $letter->employee?->user,
            'appraisal' => $letter->appraisal,
        ]);

        $filename = str_replace('/', '_', $letter->reference_no) . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Send Appraisal Reminders (HR Manual / Automated)
     */
    public function sendReminder(Request $request)
    {
        [$user, $employee, $role] = $this->getActorContext();
        if (!in_array($role, ['Super Admin', 'HR Lead'])) {
            abort(403, 'Unauthorized');
        }

        $type = $request->input('reminder_type', 'pending_self_eval');
        $appraisals = Appraisal::with(['employee.user', 'reportingManager.user', 'hod.user', 'cycle']);

        $sentCount = 0;

        if ($type === 'pending_self_eval') {
            $pending = $appraisals->where('status', 'pending_self_eval')->get();
            foreach ($pending as $app) {
                if ($app->employee && $app->employee->user_id) {
                    HrNotification::create([
                        'user_id' => $app->employee->user_id,
                        'type' => 'appraisal_reminder',
                        'title' => 'Reminder: Pending Self-Evaluation',
                        'message' => "This is a reminder from HR to submit your performance self-evaluation for {$app->cycle?->name}.",
                        'link' => route('appraisal.show', $app->id),
                        'is_read' => false,
                    ]);
                    $sentCount++;
                }
            }
        } elseif ($type === 'pending_manager_review') {
            $pending = $appraisals->whereIn('status', ['submitted_self_eval', 'pending_manager_eval'])->get();
            foreach ($pending as $app) {
                $targetUser = $app->reportingManager?->user_id ?? $app->hod?->user_id;
                if ($targetUser) {
                    HrNotification::create([
                        'user_id' => $targetUser,
                        'type' => 'appraisal_manager_reminder',
                        'title' => 'Reminder: Pending Subordinate Appraisal Review',
                        'message' => "You have a pending manager review for {$app->employee?->user?->name} in {$app->cycle?->name}.",
                        'link' => route('appraisal.show', $app->id),
                        'is_read' => false,
                    ]);
                    $sentCount++;
                }
            }
        }

        return back()->with('success', "Dispatched {$sentCount} reminder notifications successfully.");
    }

    /**
     * Analytics & Performance Reporting View
     */
    public function analytics(Request $request)
    {
        [$user, $employee, $role] = $this->getActorContext();

        $cycles = AppraisalCycle::where('status', '!=', 'Archived')->whereNotNull('financial_year')->orderBy('id', 'asc')->get();
        if ($cycles->isEmpty()) {
            $cycles = AppraisalCycle::orderBy('id', 'desc')->get();
        }
        $departments = Department::orderBy('name')->get();
        $employees = Employee::with('user')->get();

        $selectedCycleId = $request->get('cycle_id', AppraisalCycle::where('status', 'Active')->first()?->id);
        $selectedDeptId = $request->get('department_id');
        $selectedEmpId = $request->get('employee_id');

        $query = Appraisal::with(['employee.user', 'employee.department', 'employee.designation', 'cycle']);

        if ($selectedCycleId) {
            $query->where('cycle_id', $selectedCycleId);
        }
        if ($selectedDeptId) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $selectedDeptId));
        }
        if ($selectedEmpId) {
            $query->where('employee_id', $selectedEmpId);
        }

        $appraisals = $query->get();

        // Department-wise averages
        $deptAverages = [];
        foreach ($departments as $dept) {
            $deptAppraisals = $appraisals->where('employee.department_id', $dept->id)->where('status', 'completed');
            $deptAverages[$dept->name] = $deptAppraisals->count() > 0 
                ? round($deptAppraisals->avg('total_weighted_score'), 2) 
                : 0.00;
        }

        // Band distribution
        $completed = $appraisals->where('status', 'completed');
        $bandCounts = [
            'Exceptional (4.50-5.00)' => $completed->where('performance_band', 'Exceptional')->count(),
            'Exceeds Expectations (4.00-4.49)' => $completed->where('performance_band', 'Exceeds Expectations')->count(),
            'Meets Expectations (3.00-3.99)' => $completed->where('performance_band', 'Meets Expectations')->count(),
            'Needs Improvement (2.00-2.99)' => $completed->where('performance_band', 'Needs Improvement')->count(),
            'Unsatisfactory (< 2.00)' => $completed->where('performance_band', 'Unsatisfactory')->count(),
        ];

        return view('pages.appraisal-analytics', compact(
            'cycles',
            'departments',
            'employees',
            'selectedCycleId',
            'selectedDeptId',
            'selectedEmpId',
            'appraisals',
            'deptAverages',
            'bandCounts',
            'role',
            'employee'
        ));
    }
}
