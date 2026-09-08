<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Leave\Models\LeaveRequest;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Employee;
use Modules\Leave\Services\LeavePolicyService;

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

        // Determine default tab based on role if no status query param provided
        $defaultStatus = match($role) {
            'HOD / Manager', 'HR Lead', 'Super (Admin)' => 'pending_manager',
            'Employee' => 'pending_covering',
            default => 'pending_manager',
        };

        $status = $request->query('status', $defaultStatus);
        $deptId = $request->query('department_id');
        $search = $request->query('search');

        $query = LeaveRequest::with(['employee.user', 'employee.department', 'leaveType', 'coveringEmployee.user', 'managerEmployee.user']);

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
        } elseif ($role === 'Employee' && $activeEmployee) {
            // Employees only see requests where they are assigned as covering employee
            $query->where('covering_employee_id', $activeEmpId);
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
        }

        if ($deptId && in_array($role, ['Super (Admin)', 'HR Lead'])) {
            $query->whereHas('employee', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }

        if ($status === 'pending_covering') {
            $query->where('covering_status', 'Pending');
            if ($role === 'Employee' && $activeEmployee) {
                $query->where('covering_employee_id', $activeEmpId);
            }
        } elseif ($status === 'pending_manager') {
            $query->where('manager_status', 'Pending');
        } elseif ($status === 'approved') {
            $query->where(function ($q) {
                $q->where('status', 'Approved')->orWhere('manager_status', 'Approved');
            });
        } elseif ($status === 'rejected') {
            $query->where(function ($q) {
                $q->where('covering_status', 'Rejected')
                  ->orWhere('manager_status', 'Rejected')
                  ->orWhere('hr_status', 'Rejected')
                  ->orWhere('status', 'Rejected');
            });
        }

        $leaveRequests = $query->latest()->get();
        $departments = Department::all();

        // Count query helper with assigned approver scoping
        $countQuery = function ($statusType) use ($role, $activeEmployee, $activeEmpId) {
            $q = LeaveRequest::query();
            if ($role === 'HOD / Manager' && $activeEmployee) {
                $q->where(function ($subQ) use ($activeEmpId, $activeEmployee) {
                    if ($activeEmployee->department_id) {
                        $subQ->whereHas('employee', function ($eq) use ($activeEmployee) {
                            $eq->where('department_id', $activeEmployee->department_id);
                        });
                    } else {
                        $subQ->where('manager_employee_id', $activeEmpId)
                             ->orWhereHas('employee', function ($eq) use ($activeEmpId) {
                                 $eq->where('reporting_person_id', $activeEmpId);
                             });
                    }
                    $subQ->orWhere('covering_employee_id', $activeEmpId);
                });
            } elseif ($role === 'Employee' && $activeEmployee) {
                $q->where('covering_employee_id', $activeEmpId);
            }

            return match($statusType) {
                'pending_covering' => $q->where('covering_status', 'Pending')->count(),
                'pending_manager' => $q->where('manager_status', 'Pending')->count(),
                'approved' => $q->where(function ($sq) {
                    $sq->where('status', 'Approved')->orWhere('manager_status', 'Approved');
                })->count(),
                'rejected' => $q->where(function ($rq) {
                    $rq->where('covering_status', 'Rejected')->orWhere('manager_status', 'Rejected')->orWhere('hr_status', 'Rejected')->orWhere('status', 'Rejected');
                })->count(),
                'all' => $q->count(),
            };
        };

        $counts = [
            'pending_covering' => $countQuery('pending_covering'),
            'pending_manager' => $countQuery('pending_manager'),
            'approved' => $countQuery('approved'),
            'rejected' => $countQuery('rejected'),
            'all' => $countQuery('all'),
        ];

        return view('pages.approvals', compact('leaveRequests', 'departments', 'counts', 'status', 'role', 'activeEmployee'));
    }

    public function action(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        $type = $request->input('action_type'); // confirm_covering, reject_covering, manager_approve, reject_manager, hr_confirm, reject
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
}
