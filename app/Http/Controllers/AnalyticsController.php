<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Employee;
use Modules\Leave\Models\Holiday;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\EmployeeLeaveBalance;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $employee = $user ? Employee::where('user_id', $user->id)->first() : null;
        $userRole = session('current_role', $employee->system_role ?? 'Employee');
        $normalizedRole = match($userRole) {
            'Manager (Team Approvals)', 'HOD / Manager' => 'Manager',
            'HR Lead' => 'HR Lead',
            'Employee' => 'Employee',
            default => 'Super Admin',
        };

        $permissionsPath = base_path('role_module_permissions.json');
        $rolePermissions = file_exists($permissionsPath) ? json_decode(file_get_contents($permissionsPath), true) : [];

        $hasAccess = $rolePermissions[$normalizedRole]['Analytics'] ?? in_array($userRole, ['HR Lead', 'Super (Admin)', 'Super Admin']);
        if (!$hasAccess) {
            return redirect()->route('dashboard')->with('error', 'Access Denied: Reports & Leave Analytics module is restricted to HR Admin, Managers and Super Admin.');
        }

        // Active Report Tab: 'company', 'department', or 'individual'
        $activeTab = $request->query('tab', 'company');

        // Filters: Year, Month, Department ID, Employee ID, Leave Type ID
        $currentYear = (int) Carbon::now('Asia/Colombo')->year;
        $currentMonth = (int) Carbon::now('Asia/Colombo')->month;

        $selectedYear = (int) $request->query('year', $currentYear);
        $selectedMonth = $request->query('month') !== null && $request->query('month') !== '' ? (int) $request->query('month') : null;
        $selectedDeptId = $request->query('department_id') ?: null;
        $selectedEmpId = $request->query('employee_id') ?: null;
        $selectedLeaveTypeId = $request->query('leave_type_id') ?: null;

        $allDepartments = Department::orderBy('name')->get();

        // Department list for Department-Wise report
        $departments = Department::with('employees.user')
            ->when($selectedDeptId, fn($q) => $q->where('id', $selectedDeptId))
            ->get();

        // Employees for dropdown (Scoped by selected Department if chosen)
        $dropdownEmployees = Employee::with(['user', 'department', 'designation'])
            ->when($selectedDeptId, fn($q) => $q->where('department_id', $selectedDeptId))
            ->get();

        // All employees for scoped metrics
        $allEmployees = Employee::with(['user', 'department', 'designation'])
            ->when($selectedDeptId, fn($q) => $q->where('department_id', $selectedDeptId))
            ->get();

        $leaveTypes = LeaveType::where('is_active', true)->get();

        // 1. DEPARTMENT-WISE LEAVE REPORT (also used by Company-wide view)
        $departmentReport = $departments->map(function ($dept) use ($selectedYear, $selectedMonth, $selectedLeaveTypeId) {
            $deptEmpIds = $dept->employees->pluck('id');
            
            $query = LeaveRequest::with(['leaveType', 'employee.user'])
                ->whereIn('employee_id', $deptEmpIds);

            if ($selectedYear) {
                $query->where(function($q) use ($selectedYear) {
                    $q->whereYear('start_date', $selectedYear)
                      ->orWhereYear('end_date', $selectedYear);
                });
            }

            if ($selectedMonth) {
                $query->where(function($q) use ($selectedMonth) {
                    $q->whereMonth('start_date', $selectedMonth)
                      ->orWhereMonth('end_date', $selectedMonth);
                });
            }

            if ($selectedLeaveTypeId) {
                $query->where('leave_type_id', $selectedLeaveTypeId);
            }

            $leaves = $query->get();

            // Calculate total days taken (excluding Short Leaves from regular days count)
            $totalDaysTaken = $leaves->filter(fn($l) => !in_array($l->status, ['Rejected', 'Cancelled']) && !str_contains(strtolower($l->leaveType->name ?? ''), 'short'))
                ->sum(fn($l) => (float) ($l->duration ?: 1.0));

            $totalShortLeaves = $leaves->filter(fn($l) => !in_array($l->status, ['Rejected', 'Cancelled']) && str_contains(strtolower($l->leaveType->name ?? ''), 'short'))
                ->count();

            $approvedCount = $leaves->where('status', 'Approved')->count();
            $pendingCount = $leaves->whereIn('status', ['Pending', 'Pending Covering', 'Pending Manager', 'Pending HR'])->count();

            // Total Quota allocated for department employees in selected year
            $totalQuota = EmployeeLeaveBalance::whereIn('employee_id', $deptEmpIds)
                ->where('year', $selectedYear)
                ->sum('allocated') ?: ($dept->employees->count() * 28);

            $utilizationPct = $totalQuota > 0 ? min(100, round(($totalDaysTaken / $totalQuota) * 100, 1)) : 0;

            // Currently on leave today
            $today = Carbon::now('Asia/Colombo')->toDateString();
            $currentlyOnLeave = LeaveRequest::whereIn('employee_id', $deptEmpIds)
                ->where('status', 'Approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->count();

            return [
                'department' => $dept,
                'employee_count' => $dept->employees->count(),
                'total_requests' => $leaves->count(),
                'approved_count' => $approvedCount,
                'pending_count' => $pendingCount,
                'total_days_taken' => $totalDaysTaken,
                'total_short_leaves' => $totalShortLeaves,
                'total_quota' => $totalQuota,
                'utilization_pct' => $utilizationPct,
                'currently_on_leave' => $currentlyOnLeave,
                'recent_leaves' => $leaves->sortByDesc('start_date')->take(5),
            ];
        });

        // Leave type consumption breakdown for Company Overview
        $leaveTypeBreakdown = $leaveTypes->map(function ($lt) use ($selectedYear, $selectedMonth, $selectedDeptId) {
            $q = LeaveRequest::where('leave_type_id', $lt->id)
                ->whereYear('start_date', $selectedYear)
                ->when($selectedMonth, fn($sub) => $sub->whereMonth('start_date', $selectedMonth))
                ->when($selectedDeptId, fn($sub) => $sub->whereHas('employee', fn($e) => $e->where('department_id', $selectedDeptId)));

            $totalReqs = (clone $q)->count();
            $approvedDays = (clone $q)->where('status', 'Approved')->sum('duration');
            $approvedCount = (clone $q)->where('status', 'Approved')->count();
            $rate = $totalReqs > 0 ? round(($approvedCount / $totalReqs) * 100) : 0;

            return [
                'name' => $lt->name,
                'code' => $lt->code,
                'color' => $lt->color ?? '#3b82f6',
                'applications' => $totalReqs,
                'days' => $approvedDays ?: 0,
                'approval_rate' => $rate,
            ];
        });

        // 2. INDIVIDUAL EMPLOYEE LEAVE REPORT
        $individualQuery = LeaveRequest::with(['employee.user', 'employee.department', 'employee.designation', 'leaveType', 'coveringEmployee.user'])
            ->latest('start_date');

        if ($selectedYear) {
            $individualQuery->where(function($q) use ($selectedYear) {
                $q->whereYear('start_date', $selectedYear)
                  ->orWhereYear('end_date', $selectedYear);
            });
        }

        if ($selectedMonth) {
            $individualQuery->where(function($q) use ($selectedMonth) {
                $q->whereMonth('start_date', $selectedMonth)
                  ->orWhereMonth('end_date', $selectedMonth);
            });
        }

        if ($selectedDeptId) {
            $individualQuery->whereHas('employee', fn($q) => $q->where('department_id', $selectedDeptId));
        }

        if ($selectedEmpId) {
            $individualQuery->where('employee_id', $selectedEmpId);
        }

        if ($selectedLeaveTypeId) {
            $individualQuery->where('leave_type_id', $selectedLeaveTypeId);
        }

        $individualLeaves = $individualQuery->paginate(15)->withQueryString();

        // Selected single employee detailed profile if single employee selected
        $selectedEmployeeProfile = null;
        $selectedEmployeeBalances = collect();
        if ($selectedEmpId) {
            $selectedEmployeeProfile = Employee::with(['user', 'department', 'designation'])->find($selectedEmpId);
            if ($selectedEmployeeProfile) {
                $selectedEmployeeBalances = EmployeeLeaveBalance::with('leaveType')
                    ->where('employee_id', $selectedEmpId)
                    ->where('year', $selectedYear)
                    ->get();
            }
        }

        // Summary Statistics
        $totalWorkforce = $allEmployees->count();
        $todayStr = Carbon::now('Asia/Colombo')->toDateString();
        $totalOnLeaveToday = LeaveRequest::where('status', 'Approved')
            ->when($selectedDeptId, fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $selectedDeptId)))
            ->when($selectedEmpId, fn($q) => $q->where('employee_id', $selectedEmpId))
            ->where('start_date', '<=', $todayStr)
            ->where('end_date', '>=', $todayStr)
            ->count();

        $totalLeavesPeriod = LeaveRequest::whereYear('start_date', $selectedYear)
            ->when($selectedMonth, fn($q) => $q->whereMonth('start_date', $selectedMonth))
            ->when($selectedDeptId, fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $selectedDeptId)))
            ->when($selectedEmpId, fn($q) => $q->where('employee_id', $selectedEmpId))
            ->where('status', 'Approved')
            ->sum('duration') ?: 0;

        $avgDaysPerEmp = $totalWorkforce > 0 ? round($totalLeavesPeriod / $totalWorkforce, 1) : 0;

        $yearsList = range(2024, 2030);
        $monthsList = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return view('pages.analytics', compact(
            'activeTab',
            'allDepartments',
            'departments',
            'dropdownEmployees',
            'allEmployees',
            'leaveTypes',
            'departmentReport',
            'leaveTypeBreakdown',
            'individualLeaves',
            'selectedEmployeeProfile',
            'selectedEmployeeBalances',
            'selectedYear',
            'selectedMonth',
            'selectedDeptId',
            'selectedEmpId',
            'selectedLeaveTypeId',
            'yearsList',
            'monthsList',
            'totalWorkforce',
            'totalOnLeaveToday',
            'totalLeavesPeriod',
            'avgDaysPerEmp'
        ));
    }

    /**
     * Export Company-wide, Department-wise, or Individual Leave Report as CSV or PDF
     */
     public function export(Request $request)
     {
         $type = $request->query('type', 'department');
         $format = strtolower($request->query('format', 'csv')); // 'csv' or 'pdf'
         $selectedYear = (int) $request->query('year', Carbon::now('Asia/Colombo')->year);
         $selectedMonth = $request->query('month') !== null && $request->query('month') !== '' ? (int) $request->query('month') : null;
         $selectedDeptId = $request->query('department_id') ?: null;
         $selectedEmpId = $request->query('employee_id') ?: null;

         $monthsList = [
             1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
             5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
             9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
         ];

         $monthName = $selectedMonth ? ($monthsList[$selectedMonth] ?? 'Month_'.$selectedMonth) : 'All_Months';

         // === PDF EXPORT ===
        if ($format === 'pdf') {
            if ($type === 'company') {
                $departments = Department::with('employees.user')->get();
                $departmentReport = $departments->map(function ($dept) use ($selectedYear, $selectedMonth) {
                    $deptEmpIds = $dept->employees->pluck('id');

                    $leaves = LeaveRequest::with('leaveType')
                        ->whereIn('employee_id', $deptEmpIds)
                        ->where(function($q) use ($selectedYear) {
                            $q->whereYear('start_date', $selectedYear)->orWhereYear('end_date', $selectedYear);
                        })
                        ->when($selectedMonth, function($q) use ($selectedMonth) {
                            $q->where(function($sub) use ($selectedMonth) {
                                $sub->whereMonth('start_date', $selectedMonth)->orWhereMonth('end_date', $selectedMonth);
                            });
                        })
                        ->get();

                    $totalDaysTaken = $leaves->filter(fn($l) => !in_array($l->status, ['Rejected', 'Cancelled']) && !str_contains(strtolower($l->leaveType->name ?? ''), 'short'))
                        ->sum(fn($l) => (float) ($l->duration ?: 1.0));

                    $totalShortLeaves = $leaves->filter(fn($l) => !in_array($l->status, ['Rejected', 'Cancelled']) && str_contains(strtolower($l->leaveType->name ?? ''), 'short'))
                        ->count();

                    $totalQuota = EmployeeLeaveBalance::whereIn('employee_id', $deptEmpIds)
                        ->where('year', $selectedYear)
                        ->sum('allocated') ?: ($dept->employees->count() * 28);

                    $utilizationPct = $totalQuota > 0 ? min(100, round(($totalDaysTaken / $totalQuota) * 100, 1)) : 0;

                    return [
                        'department' => $dept,
                        'employee_count' => $dept->employees->count(),
                        'total_days_taken' => $totalDaysTaken,
                        'total_short_leaves' => $totalShortLeaves,
                        'total_quota' => $totalQuota,
                        'utilization_pct' => $utilizationPct,
                    ];
                });

                $leaveTypes = LeaveType::where('is_active', true)->get();
                $leaveTypeBreakdown = $leaveTypes->map(function ($lt) use ($selectedYear, $selectedMonth) {
                    $q = LeaveRequest::where('leave_type_id', $lt->id)
                        ->whereYear('start_date', $selectedYear)
                        ->when($selectedMonth, fn($sub) => $sub->whereMonth('start_date', $selectedMonth));

                    $totalReqs = (clone $q)->count();
                    $approvedDays = (clone $q)->where('status', 'Approved')->sum('duration');
                    $approvedCount = (clone $q)->where('status', 'Approved')->count();
                    $rate = $totalReqs > 0 ? round(($approvedCount / $totalReqs) * 100) : 0;

                    return [
                        'name' => $lt->name,
                        'applications' => $totalReqs,
                        'days' => $approvedDays ?: 0,
                        'approval_rate' => $rate,
                    ];
                });

                $allEmployeesCount = Employee::count();
                $todayStr = Carbon::now('Asia/Colombo')->toDateString();
                $totalOnLeaveToday = LeaveRequest::where('status', 'Approved')
                    ->where('start_date', '<=', $todayStr)
                    ->where('end_date', '>=', $todayStr)
                    ->count();

                $totalLeavesPeriod = LeaveRequest::whereYear('start_date', $selectedYear)
                    ->when($selectedMonth, fn($q) => $q->whereMonth('start_date', $selectedMonth))
                    ->where('status', 'Approved')
                    ->sum('duration') ?: 0;

                $avgDaysPerEmp = $allEmployeesCount > 0 ? round($totalLeavesPeriod / $allEmployeesCount, 1) : 0;

                $pdf = Pdf::loadView('reports.pdf-company', [
                    'departmentReport' => $departmentReport,
                    'leaveTypeBreakdown' => $leaveTypeBreakdown,
                    'departments' => $departments,
                    'selectedYear' => $selectedYear,
                    'selectedMonth' => $selectedMonth,
                    'monthsList' => $monthsList,
                    'totalWorkforce' => $allEmployeesCount,
                    'totalOnLeaveToday' => $totalOnLeaveToday,
                    'totalLeavesPeriod' => $totalLeavesPeriod,
                    'avgDaysPerEmp' => $avgDaysPerEmp,
                ])->setPaper('a4', 'portrait');

                return $pdf->download("Company_Leave_Report_{$monthName}_{$selectedYear}.pdf");

            } elseif ($type === 'department') {
                $departments = Department::with('employees.user')
                    ->when($selectedDeptId, fn($q) => $q->where('id', $selectedDeptId))
                    ->get();
                $departmentReport = $departments->map(function ($dept) use ($selectedYear, $selectedMonth) {
                    $deptEmpIds = $dept->employees->pluck('id');

                    $leaves = LeaveRequest::with('leaveType')
                        ->whereIn('employee_id', $deptEmpIds)
                        ->where(function($q) use ($selectedYear) {
                            $q->whereYear('start_date', $selectedYear)->orWhereYear('end_date', $selectedYear);
                        })
                        ->when($selectedMonth, function($q) use ($selectedMonth) {
                            $q->where(function($sub) use ($selectedMonth) {
                                $sub->whereMonth('start_date', $selectedMonth)->orWhereMonth('end_date', $selectedMonth);
                            });
                        })
                        ->get();

                    $totalDaysTaken = $leaves->filter(fn($l) => !in_array($l->status, ['Rejected', 'Cancelled']) && !str_contains(strtolower($l->leaveType->name ?? ''), 'short'))
                        ->sum(fn($l) => (float) ($l->duration ?: 1.0));

                    $totalShortLeaves = $leaves->filter(fn($l) => !in_array($l->status, ['Rejected', 'Cancelled']) && str_contains(strtolower($l->leaveType->name ?? ''), 'short'))
                        ->count();

                    $totalQuota = EmployeeLeaveBalance::whereIn('employee_id', $deptEmpIds)
                        ->where('year', $selectedYear)
                        ->sum('allocated') ?: ($dept->employees->count() * 28);

                    $utilizationPct = $totalQuota > 0 ? min(100, round(($totalDaysTaken / $totalQuota) * 100, 1)) : 0;

                    return [
                        'department' => $dept,
                        'employee_count' => $dept->employees->count(),
                        'total_days_taken' => $totalDaysTaken,
                        'total_short_leaves' => $totalShortLeaves,
                        'total_quota' => $totalQuota,
                        'utilization_pct' => $utilizationPct,
                    ];
                });

                $allEmployeesCount = Employee::when($selectedDeptId, fn($q) => $q->where('department_id', $selectedDeptId))->count();
                $todayStr = Carbon::now('Asia/Colombo')->toDateString();
                $totalOnLeaveToday = LeaveRequest::where('status', 'Approved')
                    ->when($selectedDeptId, fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $selectedDeptId)))
                    ->where('start_date', '<=', $todayStr)
                    ->where('end_date', '>=', $todayStr)
                    ->count();

                $totalLeavesPeriod = LeaveRequest::whereYear('start_date', $selectedYear)
                    ->when($selectedMonth, fn($q) => $q->whereMonth('start_date', $selectedMonth))
                    ->when($selectedDeptId, fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $selectedDeptId)))
                    ->where('status', 'Approved')
                    ->sum('duration') ?: 0;

                $avgDaysPerEmp = $allEmployeesCount > 0 ? round($totalLeavesPeriod / $allEmployeesCount, 1) : 0;

                // If single department is selected, load detailed staff and leave applications for comprehensive report
                $targetDepartment = $selectedDeptId ? Department::with(['employees.user', 'employees.designation', 'employees.leaveBalances.leaveType'])->find($selectedDeptId) : null;
                
                $departmentEmployees = collect();
                $departmentLeaves = collect();

                if ($selectedDeptId && $targetDepartment) {
                    $departmentEmployees = $targetDepartment->employees;
                    $empIds = $departmentEmployees->pluck('id');

                    $departmentLeaves = LeaveRequest::with(['employee.user', 'employee.designation', 'leaveType', 'coveringEmployee.user'])
                        ->whereIn('employee_id', $empIds)
                        ->whereYear('start_date', $selectedYear)
                        ->when($selectedMonth, fn($q) => $q->whereMonth('start_date', $selectedMonth))
                        ->latest('start_date')
                        ->get();
                }

                $leaveTypes = LeaveType::where('is_active', true)->orderBy('id')->get();

                $pdf = Pdf::loadView('reports.pdf-department', [
                    'departmentReport' => $departmentReport,
                    'selectedYear' => $selectedYear,
                    'selectedMonth' => $selectedMonth,
                    'monthsList' => $monthsList,
                    'totalWorkforce' => $allEmployeesCount,
                    'totalOnLeaveToday' => $totalOnLeaveToday,
                    'totalLeavesPeriod' => $totalLeavesPeriod,
                    'avgDaysPerEmp' => $avgDaysPerEmp,
                    'targetDepartment' => $targetDepartment,
                    'departmentEmployees' => $departmentEmployees,
                    'departmentLeaves' => $departmentLeaves,
                    'leaveTypes' => $leaveTypes,
                ])->setPaper('a4', 'landscape');

                $deptNameSlug = $targetDepartment ? str_replace(' ', '_', $targetDepartment->name) : 'All_Departments';
                return $pdf->download("Department_Leave_Report_{$deptNameSlug}_{$monthName}_{$selectedYear}.pdf");
            } else {
                $query = LeaveRequest::with(['employee.user', 'employee.department', 'employee.designation', 'leaveType', 'coveringEmployee.user'])
                    ->whereYear('start_date', $selectedYear)
                    ->when($selectedMonth, fn($q) => $q->whereMonth('start_date', $selectedMonth))
                    ->when($selectedDeptId, fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $selectedDeptId)))
                    ->when($selectedEmpId, fn($q) => $q->where('employee_id', $selectedEmpId))
                    ->latest('start_date');

                $leaves = $query->get();

                $selectedEmployeeProfile = null;
                $selectedEmployeeBalances = collect();
                if ($selectedEmpId) {
                    $selectedEmployeeProfile = Employee::with(['user', 'department', 'designation'])->find($selectedEmpId);
                    if ($selectedEmployeeProfile) {
                        $selectedEmployeeBalances = EmployeeLeaveBalance::with('leaveType')
                            ->where('employee_id', $selectedEmpId)
                            ->where('year', $selectedYear)
                            ->get();
                    }
                }

                $pdf = Pdf::loadView('reports.pdf-individual', [
                    'leaves' => $leaves,
                    'selectedYear' => $selectedYear,
                    'selectedMonth' => $selectedMonth,
                    'monthsList' => $monthsList,
                    'selectedEmployeeProfile' => $selectedEmployeeProfile,
                    'selectedEmployeeBalances' => $selectedEmployeeBalances,
                ])->setPaper('a4', 'landscape');

                return $pdf->download("Individual_Leave_Report_{$monthName}_{$selectedYear}.pdf");
            }
        }

         // === CSV EXPORT ===
         $filename = "Loops_HR_Leave_Report_{$type}_{$monthName}_{$selectedYear}.csv";

         $headers = [
             'Content-Type' => 'text/csv',
             'Content-Disposition' => "attachment; filename=\"{$filename}\"",
         ];

         $callback = function () use ($type, $selectedYear, $selectedMonth, $selectedDeptId, $selectedEmpId) {
             $handle = fopen('php://output', 'w');

            if ($type === 'company') {
                fputcsv($handle, ['Department Name', 'Code', 'HOD', 'Total Employees', 'Total Leave Days Taken', 'Short Leaves Count', 'Total Quota', 'Utilization %', 'Staff On Leave Today']);

                $departments = Department::with('employees.user')->get();
                foreach ($departments as $dept) {
                    $deptEmpIds = $dept->employees->pluck('id');
                    
                    $leaves = LeaveRequest::with('leaveType')
                        ->whereIn('employee_id', $deptEmpIds)
                        ->whereYear('start_date', $selectedYear)
                        ->when($selectedMonth, fn($q) => $q->whereMonth('start_date', $selectedMonth))
                        ->get();

                    $totalDays = $leaves->filter(fn($l) => !in_array($l->status, ['Rejected', 'Cancelled']) && !str_contains(strtolower($l->leaveType->name ?? ''), 'short'))
                        ->sum(fn($l) => (float) ($l->duration ?: 1.0));

                    $shortLeaves = $leaves->filter(fn($l) => !in_array($l->status, ['Rejected', 'Cancelled']) && str_contains(strtolower($l->leaveType->name ?? ''), 'short'))
                        ->count();

                    $totalQuota = EmployeeLeaveBalance::whereIn('employee_id', $deptEmpIds)
                        ->where('year', $selectedYear)
                        ->sum('allocated') ?: ($dept->employees->count() * 28);

                    $utilPct = $totalQuota > 0 ? round(($totalDays / $totalQuota) * 100, 1) : 0;

                    $today = Carbon::now('Asia/Colombo')->toDateString();
                    $todayCount = LeaveRequest::whereIn('employee_id', $deptEmpIds)
                        ->where('status', 'Approved')
                        ->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today)
                        ->count();

                    fputcsv($handle, [
                        $dept->name,
                        $dept->code,
                        $dept->hod_name ?? 'Super Admin',
                        $dept->employees->count(),
                        $totalDays,
                        $shortLeaves,
                        $totalQuota,
                        $utilPct . '%',
                        $todayCount
                    ]);
                }
             } elseif ($type === 'department') {
                 $leaveTypes = LeaveType::where('is_active', true)->orderBy('id')->get();
                 $headersRow = ['EMP ID', 'Employee Name', 'Department', 'Designation'];
                 foreach ($leaveTypes as $lt) {
                     $headersRow[] = $lt->name . ' (Days)';
                 }
                 $headersRow[] = 'Total Days Taken';
                 fputcsv($handle, $headersRow);

                 $employees = Employee::with(['user', 'department', 'designation'])
                     ->when($selectedDeptId, fn($q) => $q->where('department_id', $selectedDeptId))
                     ->get();

                 $deptEmpIds = $employees->pluck('id');
                 $allLeaves = LeaveRequest::whereIn('employee_id', $deptEmpIds)
                     ->whereYear('start_date', $selectedYear)
                     ->when($selectedMonth, fn($q) => $q->whereMonth('start_date', $selectedMonth))
                     ->whereNotIn('status', ['Rejected', 'Cancelled'])
                     ->get();

                 foreach ($employees as $emp) {
                     $empLeaves = $allLeaves->where('employee_id', $emp->id);
                     $row = [
                         $emp->employee_id_number ?? 'N/A',
                         $emp->user->name ?? 'N/A',
                         $emp->department->name ?? 'N/A',
                         $emp->designation->name ?? 'Employee'
                     ];

                     $totalDays = 0;
                     foreach ($leaveTypes as $lt) {
                         $days = $empLeaves->where('leave_type_id', $lt->id)->sum(fn($l) => (float) ($l->duration ?: 1.0));
                         $row[] = (float) $days;
                         $totalDays += $days;
                     }
                     $row[] = (float) $totalDays;

                     fputcsv($handle, $row);
                 }
             } else {
                 // For individual type, export comprehensive employee leave records
                 fputcsv($handle, ['Department', 'Employee Name', 'EMP ID', 'Designation', 'Leave Type', 'Start Date', 'End Date', 'Duration (Days)', 'Status', 'Reason', 'Covering Colleague', 'Applied At']);

                 $query = LeaveRequest::with(['employee.user', 'employee.department', 'employee.designation', 'leaveType', 'coveringEmployee.user'])
                     ->whereYear('start_date', $selectedYear)
                     ->when($selectedMonth, fn($q) => $q->whereMonth('start_date', $selectedMonth))
                     ->when($selectedDeptId, fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $selectedDeptId)))
                     ->when($selectedEmpId, fn($q) => $q->where('employee_id', $selectedEmpId))
                     ->latest('start_date');

                 foreach ($query->cursor() as $req) {
                     fputcsv($handle, [
                         $req->employee?->department?->name ?? 'N/A',
                         $req->employee?->user?->name ?? 'N/A',
                         $req->employee?->employee_id_number ?? 'N/A',
                         $req->employee?->designation?->name ?? 'N/A',
                         $req->leaveType?->name ?? 'N/A',
                         $req->start_date,
                         $req->end_date,
                         $req->duration ?: 1.0,
                         $req->status,
                         $req->reason ?? '',
                         $req->coveringEmployee?->user?->name ?? 'None',
                         $req->created_at ? $req->created_at->format('Y-m-d H:i') : ''
                     ]);
                 }
             }

             fclose($handle);
         };

         return response()->stream($callback, 200, $headers);
     }
}
