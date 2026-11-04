<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\EmployeeLeaveBalance;
use Modules\Leave\Models\LieuLeaveClaim;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Employee;
use Modules\Leave\Services\LeavePolicyService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApprovalController extends Controller
{
    protected LeavePolicyService $leavePolicyService;

    public function __construct(LeavePolicyService $leavePolicyService)
    {
        $this->leavePolicyService = $leavePolicyService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $activeEmployee = $user ? (Employee::where('user_id', $user->id)->first() ?? Employee::first()) : Employee::first();
        $activeEmpId = $activeEmployee?->id;

        $role = match($activeEmployee->system_role ?? '') {
            'HR Lead' => 'HR Lead',
            'Manager (Team Approvals)' => 'HOD / Manager',
            'Employee' => 'Employee',
            default => 'Super (Admin)',
        };

        if (session('current_role')) {
            $sessionRole = session('current_role');
            $role = match($sessionRole) {
                'HR Lead' => 'HR Lead',
                'Manager (Team Approvals)', 'HOD / Manager' => 'HOD / Manager',
                'Employee' => 'Employee',
                default => 'Super (Admin)',
            };
        }

        if ($role === 'Employee') {
            return redirect()->route('dashboard')->with('error', 'Access Denied: Approvals page is restricted to Managers, HODs, and HR Admins.');
        }

        // Determine active sub-tab ('leaves' or 'lieu')
        $tab = $request->query('tab', 'leaves');

        // Determine status filter
        $defaultStatus = 'pending_manager';
        $status = $request->query('status', $defaultStatus);
        $deptId = $request->query('department_id');
        $search = $request->query('search');
        $month = $request->query('month');
        $year = $request->query('year');

        // Base Query for Leave Requests
        $query = LeaveRequest::with(['employee.user', 'employee.department', 'employee.designation', 'leaveType', 'coveringEmployee.user', 'managerEmployee.user']);

        // Base Query for Lieu Claims
        $claimQuery = LieuLeaveClaim::with(['employee.user', 'employee.department', 'employee.designation', 'managerEmployee.user']);

        // Scope Manager role strictly to their department employees
        if ($role === 'HOD / Manager' && $activeEmployee) {
            $query->where(function ($q) use ($activeEmpId, $activeEmployee) {
                if ($activeEmployee->department_id) {
                    $q->whereHas('employee', function ($eq) use ($activeEmployee) {
                        $eq->where('department_id', $activeEmployee->department_id);
                    });
                } else {
                    $q->where('manager_employee_id', $activeEmpId)
                      ->orWhereHas('employee', function ($eq) use ($activeEmpId) {
                          $eq->where('reporting_person_id', $activeEmpId);
                      });
                }
                $q->orWhere('covering_employee_id', $activeEmpId);
            });

            $claimQuery->where(function ($q) use ($activeEmpId, $activeEmployee) {
                if ($activeEmployee->department_id) {
                    $q->whereHas('employee', function ($eq) use ($activeEmployee) {
                        $eq->where('department_id', $activeEmployee->department_id);
                    });
                } else {
                    $q->whereHas('employee', function ($eq) use ($activeEmpId) {
                        $eq->where('reporting_person_id', $activeEmpId);
                    });
                }
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee.user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhere('req_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('leaveType', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });

            $claimQuery->where(function ($q) use ($search) {
                $q->whereHas('employee.user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhere('claim_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if ($deptId && in_array($role, ['Super (Admin)', 'HR Lead'])) {
            $query->whereHas('employee', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
            $claimQuery->whereHas('employee', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }

        if ($month && $year) {
            $query->where(function ($q) use ($month, $year) {
                $q->where(function ($mq) use ($month) {
                    $mq->whereMonth('start_date', $month)
                       ->orWhereMonth('end_date', $month);
                })->where(function ($yq) use ($year) {
                    $yq->whereYear('start_date', $year)
                       ->orWhereYear('end_date', $year);
                });
            });
            $claimQuery->whereMonth('worked_date', $month)->whereYear('worked_date', $year);
        } elseif ($year) {
            $query->where(function ($q) use ($year) {
                $q->whereYear('start_date', $year)
                   ->orWhereYear('end_date', $year);
            });
            $claimQuery->whereYear('worked_date', $year);
        }

        // Apply status filter to queries
        $filteredQuery = clone $query;
        if ($status === 'pending_manager') {
            $filteredQuery->where('status', 'Pending Manager Approval');
        } elseif ($status === 'approved') {
            $filteredQuery->where(function ($q) {
                $q->where('status', 'Approved')->orWhere('manager_status', 'Approved');
            });
        } elseif ($status === 'rejected') {
            $filteredQuery->where(function ($q) {
                $q->where('covering_status', 'Rejected')
                  ->orWhere('manager_status', 'Rejected')
                  ->orWhere('hr_status', 'Rejected')
                  ->orWhere('status', 'Rejected');
            });
        }

        $filteredClaimQuery = clone $claimQuery;
        if ($status === 'pending_manager') {
            $filteredClaimQuery->where('status', 'Pending Manager Approval');
        } elseif ($status === 'approved') {
            $filteredClaimQuery->where('status', 'Approved');
        } elseif ($status === 'rejected') {
            $filteredClaimQuery->where('status', 'Rejected');
        }

        $leaveRequests = $filteredQuery->orderByDesc('created_at')->get();
        $lieuClaims = $filteredClaimQuery->orderByDesc('worked_date')->get();
        $departments = Department::orderBy('name')->get();

        // Calculate counts
        $counts = [
            'pending_covering' => (clone $query)->where('covering_status', 'Pending')->count(),
            'pending_manager' => (clone $query)->where('manager_status', 'Pending')->count(),
            'approved' => (clone $query)->where(function ($sq) {
                $sq->where('status', 'Approved')->orWhere('manager_status', 'Approved');
            })->count(),
            'rejected' => (clone $query)->where(function ($rq) {
                $rq->where('covering_status', 'Rejected')->orWhere('manager_status', 'Rejected')->orWhere('hr_status', 'Rejected')->orWhere('status', 'Rejected');
            })->count(),
            'all' => (clone $query)->count(),

            // Lieu claims counts
            'lieu_pending' => (clone $claimQuery)->where('status', 'Pending Manager Approval')->count(),
            'lieu_approved' => (clone $claimQuery)->where('status', 'Approved')->count(),
            'lieu_rejected' => (clone $claimQuery)->where('status', 'Rejected')->count(),
            'lieu_all' => (clone $claimQuery)->count(),
        ];

        $monthsList = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        $yearsList = range(now()->year - 2, now()->year + 2);

        return view('pages.approvals', compact(
            'leaveRequests', 'lieuClaims', 'tab', 'departments', 'counts', 
            'status', 'role', 'activeEmployee', 'monthsList', 'yearsList', 
            'month', 'year', 'deptId', 'search'
        ));
    }

    public function action(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        $type = $request->input('action_type');
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;
        $role = match($activeEmp->system_role ?? '') {
            'HR Lead' => 'HR Lead',
            'Manager (Team Approvals)' => 'HOD / Manager',
            'Employee' => 'Employee',
            default => 'Super (Admin)',
        };

        if (session('current_role')) {
            $sessionRole = session('current_role');
            $role = match($sessionRole) {
                'HR Lead' => 'HR Lead',
                'Manager (Team Approvals)', 'HOD / Manager' => 'HOD / Manager',
                'Employee' => 'Employee',
                default => 'Super (Admin)',
            };
        }

        if ($role === 'Employee') {
            return redirect()->back()->with('error', 'Unauthorized: Regular employees do not have privileges to approve leaves.');
        }

        // Self-approval restriction
        if (in_array($type, ['manager_approve', 'reject_manager']) && $activeEmp && $leaveRequest->employee_id === $activeEmp->id && $role !== 'Super (Admin)') {
            return redirect()->back()->with('error', 'Unauthorized: Managers cannot approve or reject their own leave requests.');
        }

        // Role action authorization checks
        if (in_array($type, ['manager_approve', 'reject_manager']) && !in_array($role, ['HOD / Manager', 'Super (Admin)', 'HR Lead'])) {
            return redirect()->back()->with('error', 'Unauthorized: Only assigned Managers, HR Admin, or Super Admin can grant Manager approvals.');
        }

        if ($type === 'confirm_covering') {
            $leaveRequest->update([
                'covering_status' => 'Approved',
                'status' => 'Pending Manager Approval'
            ]);
            \App\Services\NotificationService::notifyCoveringConfirmed($leaveRequest);
            $msg = 'Covering employee assignment confirmed! Request is now pending Manager approval.';
        } elseif ($type === 'reject_covering') {
            $leaveRequest->update(['covering_status' => 'Rejected', 'status' => 'Rejected']);
            $this->leavePolicyService->refundLeaveBalance($leaveRequest);
            \App\Services\NotificationService::notifyLeaveRejected($leaveRequest, 'Covering Employee');
            $msg = 'Leave request rejected by covering employee and days refunded to balance.';
        } elseif ($type === 'manager_approve' || $type === 'hr_confirm') {
            $leaveRequest->update([
                'manager_status' => 'Approved',
                'hr_status' => 'Approved',
                'status' => 'Approved',
                'manager_employee_id' => $activeEmp?->id ?? $leaveRequest->manager_employee_id,
            ]);
            \App\Services\NotificationService::notifyFinalApproved($leaveRequest, $activeEmp?->user?->name ?? 'Line Manager');
            $msg = 'Line Manager approval granted! Leave request is fully approved.';
        } elseif ($type === 'reject_manager' || $type === 'reject') {
            $leaveRequest->update(['manager_status' => 'Rejected', 'hr_status' => 'Rejected', 'status' => 'Rejected']);
            $this->leavePolicyService->refundLeaveBalance($leaveRequest);
            \App\Services\NotificationService::notifyLeaveRejected($leaveRequest, 'Line Manager');
            $msg = 'Leave request rejected and days refunded to balance.';
        } else {
            $msg = 'Action processed.';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function actionLieuClaim(Request $request, $id)
    {
        $claim = LieuLeaveClaim::findOrFail($id);
        $type = $request->input('action_type'); // approve, reject
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;
        $role = match($activeEmp->system_role ?? '') {
            'HR Lead' => 'HR Lead',
            'Manager (Team Approvals)' => 'HOD / Manager',
            'Employee' => 'Employee',
            default => 'Super (Admin)',
        };

        if (session('current_role')) {
            $sessionRole = session('current_role');
            $role = match($sessionRole) {
                'HR Lead' => 'HR Lead',
                'Manager (Team Approvals)', 'HOD / Manager' => 'HOD / Manager',
                'Employee' => 'Employee',
                default => 'Super (Admin)',
            };
        }

        if ($role === 'Employee') {
            return redirect()->back()->with('error', 'Unauthorized: Regular employees do not have privileges to approve claims.');
        }

        // Self-approval restriction
        if ($activeEmp && $claim->employee_id === $activeEmp->id && $role !== 'Super (Admin)') {
            return redirect()->back()->with('error', 'Unauthorized: Managers cannot approve or reject their own lieu claims.');
        }

        if ($type === 'approve') {
            DB::transaction(function() use ($claim, $activeEmp) {
                $claim->update([
                    'status' => 'Approved',
                    'manager_employee_id' => $activeEmp?->id,
                    'approved_at' => now(),
                    'expires_at' => now()->addDays(30),
                ]);

                // Credit employee's LIEU balance
                $lieuType = LeaveType::where('code', 'LIEU')->first();
                if ($lieuType) {
                    $balance = EmployeeLeaveBalance::firstOrCreate(
                        [
                            'employee_id' => $claim->employee_id,
                            'leave_type_id' => $lieuType->id,
                            'year' => now()->year,
                        ],
                        [
                            'allocated' => 0,
                            'used' => 0,
                            'carried_forward' => 0,
                        ]
                    );
                    $balance->increment('allocated', (float)$claim->credit_days);
                }

                \App\Services\NotificationService::notifyLieuClaimApproved($claim, $activeEmp?->user?->name ?? 'Line Manager');
            });

            return redirect()->back()->with('success', "Lieu leave claim approved! {$claim->credit_days} day(s) credited to employee balance (30 days validity).");
        } elseif ($type === 'reject') {
            $claim->update([
                'status' => 'Rejected',
                'manager_employee_id' => $activeEmp?->id,
                'rejection_reason' => $request->input('rejection_reason'),
            ]);

            \App\Services\NotificationService::notifyLieuClaimRejected($claim, $activeEmp?->user?->name ?? 'Line Manager');

            return redirect()->back()->with('success', 'Lieu leave claim has been rejected.');
        }

        return redirect()->back();
    }
}
