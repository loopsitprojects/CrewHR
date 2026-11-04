<?php

namespace App\Services;

use App\Models\HrNotification;
use Modules\Leave\Models\LeaveRequest;
use App\Models\User;
use Modules\Employee\Models\Employee;

class NotificationService
{
    /**
     * Triggered when a new leave request is submitted by an employee.
     */
    public static function notifyLeaveApplied(LeaveRequest $leaveRequest): void
    {
        $employeeName = $leaveRequest->employee->user->name ?? 'Employee';
        $leaveTypeName = $leaveRequest->leaveType->name ?? 'Leave';

        // 1. Notify Covering Employee if assigned and pending
        if ($leaveRequest->covering_employee_id && $leaveRequest->covering_status === 'Pending') {
            $coveringEmp = Employee::find($leaveRequest->covering_employee_id);
            if ($coveringEmp && $coveringEmp->user_id) {
                HrNotification::create([
                    'user_id' => $coveringEmp->user_id,
                    'type' => 'covering_request',
                    'title' => 'Covering Duty Approval Request',
                    'message' => "{$employeeName} has requested you as covering person for {$leaveTypeName} ({$leaveRequest->duration} days).",
                    'link' => route('approvals.index', ['status' => 'pending_covering']),
                    'is_read' => false,
                ]);
            }
        }

        // 2. Notify Manager
        $managerUserId = null;
        if ($leaveRequest->manager_employee_id) {
            $managerEmp = Employee::find($leaveRequest->manager_employee_id);
            $managerUserId = $managerEmp?->user_id;
        } elseif ($leaveRequest->employee?->reporting_person_id) {
            $managerEmp = Employee::find($leaveRequest->employee->reporting_person_id);
            $managerUserId = $managerEmp?->user_id;
        }

        if ($managerUserId) {
            HrNotification::create([
                'user_id' => $managerUserId,
                'type' => 'manager_approval_request',
                'title' => 'New Leave Approval Request',
                'message' => "{$employeeName} has submitted a {$leaveTypeName} request ({$leaveRequest->duration} days) requiring your approval.",
                'link' => route('approvals.index', ['status' => 'pending_manager']),
                'is_read' => false,
            ]);
        }

        // 3. Notify Super Admins & HR Leads
        $adminUserIds = User::whereHas('employee', function ($q) {
            $q->whereIn('system_role', ['Super (Admin)', 'HR Lead']);
        })->pluck('id')->toArray();

        foreach (array_unique($adminUserIds) as $adminId) {
            if ($adminId !== $managerUserId) {
                HrNotification::create([
                    'user_id' => $adminId,
                    'type' => 'leave_applied',
                    'title' => 'New Leave Application',
                    'message' => "{$employeeName} applied for {$leaveTypeName} ({$leaveRequest->duration} days).",
                    'link' => route('approvals.index'),
                    'is_read' => false,
                ]);
            }
        }
    }

    /**
     * Triggered when covering employee confirms assignment.
     */
    public static function notifyCoveringConfirmed(LeaveRequest $leaveRequest): void
    {
        $employeeUser = $leaveRequest->employee->user ?? null;
        $coveringName = $leaveRequest->coveringEmployee->user->name ?? 'Covering Person';
        $leaveTypeName = $leaveRequest->leaveType->name ?? 'Leave';

        // Notify Applying Employee
        if ($employeeUser) {
            HrNotification::create([
                'user_id' => $employeeUser->id,
                'type' => 'covering_confirmed',
                'title' => 'Covering Duty Confirmed',
                'message' => "{$coveringName} confirmed covering duty for your {$leaveTypeName} request.",
                'link' => route('my-leaves.index'),
                'is_read' => false,
            ]);
        }

        // Notify Manager
        $managerUserId = null;
        if ($leaveRequest->manager_employee_id) {
            $managerEmp = Employee::find($leaveRequest->manager_employee_id);
            $managerUserId = $managerEmp?->user_id;
        } elseif ($leaveRequest->employee?->reporting_person_id) {
            $managerEmp = Employee::find($leaveRequest->employee->reporting_person_id);
            $managerUserId = $managerEmp?->user_id;
        }

        if ($managerUserId) {
            $empName = $employeeUser->name ?? 'Employee';
            HrNotification::create([
                'user_id' => $managerUserId,
                'type' => 'manager_approval_request',
                'title' => 'Covering Confirmed - Pending Manager Approval',
                'message' => "{$coveringName} confirmed covering duty for {$empName}'s {$leaveTypeName} request. Pending your approval.",
                'link' => route('approvals.index', ['status' => 'pending_manager']),
                'is_read' => false,
            ]);
        }
    }

    /**
     * Triggered when manager approves the leave request.
     */
    public static function notifyManagerApproved(LeaveRequest $leaveRequest, string $managerName): void
    {
        $employeeUser = $leaveRequest->employee->user ?? null;
        $leaveTypeName = $leaveRequest->leaveType->name ?? 'Leave';
        $empName = $employeeUser->name ?? 'Employee';

        // 1. Notify Applying Employee
        if ($employeeUser) {
            HrNotification::create([
                'user_id' => $employeeUser->id,
                'type' => 'leave_manager_approved',
                'title' => 'Manager Approval Granted 🎉',
                'message' => "Your {$leaveTypeName} request was approved by {$managerName}. Pending final HR sign-off.",
                'link' => route('my-leaves.index'),
                'is_read' => false,
            ]);
        }

        // 2. Notify HR Leads & Super Admins
        $adminUserIds = User::whereHas('employee', function ($q) {
            $q->whereIn('system_role', ['Super (Admin)', 'HR Lead']);
        })->pluck('id')->toArray();

        foreach (array_unique($adminUserIds) as $adminId) {
            HrNotification::create([
                'user_id' => $adminId,
                'type' => 'leave_pending_hr',
                'title' => 'Pending HR Sign-off',
                'message' => "Manager {$managerName} approved {$leaveTypeName} for {$empName}. Pending final HR sign-off.",
                'link' => route('approvals.index', ['status' => 'pending_hr']),
                'is_read' => false,
            ]);
        }
    }

    /**
     * Triggered when HR Admin gives final sign-off.
     */
    public static function notifyFinalApproved(LeaveRequest $leaveRequest, string $approverRole): void
    {
        $employeeUser = $leaveRequest->employee->user ?? null;
        $leaveTypeName = $leaveRequest->leaveType->name ?? 'Leave';

        if ($employeeUser) {
            HrNotification::create([
                'user_id' => $employeeUser->id,
                'type' => 'leave_approved',
                'title' => 'Leave Request Approved 🎉',
                'message' => "Your {$leaveTypeName} request ({$leaveRequest->start_date}) received final {$approverRole} approval.",
                'link' => route('my-leaves.index'),
                'is_read' => false,
            ]);
        }
    }

    public static function notifyLeaveApproved(LeaveRequest $leaveRequest, string $approverRole): void
    {
        self::notifyFinalApproved($leaveRequest, $approverRole);
    }

    public static function notifyLeaveRejected(LeaveRequest $leaveRequest, string $rejectorRole): void
    {
        $employeeUser = $leaveRequest->employee->user ?? null;
        $leaveTypeName = $leaveRequest->leaveType->name ?? 'Leave';

        if ($employeeUser) {
            HrNotification::create([
                'user_id' => $employeeUser->id,
                'type' => 'leave_rejected',
                'title' => 'Leave Request Rejected',
                'message' => "Your {$leaveTypeName} request ({$leaveRequest->start_date}) was rejected by {$rejectorRole}. Days have been refunded.",
                'link' => route('my-leaves.index'),
                'is_read' => false,
            ]);
        }
    }

    public static function notifyLeaveCanceled(LeaveRequest $leaveRequest): void
    {
        $employeeUser = $leaveRequest->employee->user ?? null;
        $leaveTypeName = $leaveRequest->leaveType->name ?? 'Leave';

        if ($employeeUser) {
            HrNotification::create([
                'user_id' => $employeeUser->id,
                'type' => 'leave_canceled',
                'title' => 'Leave Request Canceled',
                'message' => "Your {$leaveTypeName} request ({$leaveRequest->start_date}) was canceled. Days have been refunded.",
                'link' => route('my-leaves.index'),
                'is_read' => false,
            ]);
        }
    }

    public static function notifyLieuClaimSubmitted(\Modules\Leave\Models\LieuLeaveClaim $claim): void
    {
        $employeeName = $claim->employee->user->name ?? 'Employee';
        $durationLabel = $claim->duration_type === 'full_day' ? 'Full Day (8h)' : 'Half Day (4h)';
        $dateFormatted = \Carbon\Carbon::parse($claim->worked_date)->format('D, j M Y');
        $hours = (float)$claim->hours_worked;

        $managerUserId = null;
        if ($claim->employee?->reporting_person_id) {
            $managerEmp = Employee::find($claim->employee->reporting_person_id);
            $managerUserId = $managerEmp?->user_id;
        }

        if ($managerUserId) {
            HrNotification::create([
                'user_id' => $managerUserId,
                'type' => 'lieu_claim_request',
                'title' => 'New Lieu Leave Request',
                'message' => "{$employeeName} submitted a {$durationLabel} ({$hours} work hours) Lieu Leave request for {$dateFormatted} requiring your approval.",
                'link' => route('approvals.index', ['tab' => 'lieu']),
                'is_read' => false,
            ]);
        }

        $adminUserIds = User::whereHas('employee', function ($q) {
            $q->whereIn('system_role', ['Super (Admin)', 'HR Lead']);
        })->pluck('id')->toArray();

        foreach ($adminUserIds as $adminId) {
            if ($adminId !== $managerUserId) {
                HrNotification::create([
                    'user_id' => $adminId,
                    'type' => 'lieu_claim_request',
                    'title' => 'New Lieu Leave Request',
                    'message' => "{$employeeName} submitted a {$durationLabel} ({$hours} work hours) Lieu Leave request for {$dateFormatted} requiring your approval.",
                    'link' => route('approvals.index', ['tab' => 'lieu']),
                    'is_read' => false,
                ]);
            }
        }
    }

    public static function notifyLieuClaimApproved(\Modules\Leave\Models\LieuLeaveClaim $claim, string $approverName): void
    {
        $employeeUser = $claim->employee->user ?? null;
        $credit = (float)$claim->credit_days;
        $dateFormatted = \Carbon\Carbon::parse($claim->worked_date)->format('D, j M Y');

        if ($employeeUser) {
            HrNotification::create([
                'user_id' => $employeeUser->id,
                'type' => 'lieu_claim_approved',
                'title' => 'Lieu Leave Claim Approved 🎉',
                'message' => "Your Lieu Leave claim for {$dateFormatted} (+{$credit} day) has been approved by {$approverName}. Valid for 30 days.",
                'link' => route('leave.dashboard'),
                'is_read' => false,
            ]);
        }
    }

    public static function notifyLieuClaimRejected(\Modules\Leave\Models\LieuLeaveClaim $claim, string $rejectorName): void
    {
        $employeeUser = $claim->employee->user ?? null;
        $dateFormatted = \Carbon\Carbon::parse($claim->worked_date)->format('D, j M Y');

        if ($employeeUser) {
            HrNotification::create([
                'user_id' => $employeeUser->id,
                'type' => 'lieu_claim_rejected',
                'title' => 'Lieu Leave Claim Rejected',
                'message' => "Your Lieu Leave claim for {$dateFormatted} was rejected by {$rejectorName}." . ($claim->rejection_reason ? " Reason: {$claim->rejection_reason}" : ''),
                'link' => route('leave.dashboard'),
                'is_read' => false,
            ]);
        }
    }
}
