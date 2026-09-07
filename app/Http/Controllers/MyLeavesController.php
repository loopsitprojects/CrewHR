<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Models\EmployeeLeaveBalance;
use Modules\Employee\Models\Employee;
use Modules\Leave\Services\LeavePolicyService;
use Carbon\Carbon;

class MyLeavesController extends Controller
{
    protected LeavePolicyService $leavePolicyService;

    public function __construct(LeavePolicyService $leavePolicyService)
    {
        $this->leavePolicyService = $leavePolicyService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $employee = $user ? (Employee::where('user_id', $user->id)->first() ?? Employee::first()) : Employee::first();

        $status = $request->query('status', 'all');
        $search = $request->query('search');
        $leaveTypeId = $request->query('leave_type_id');
        $year = (int)$request->query('year', Carbon::now('Asia/Colombo')->year);
        $month = $request->query('month');

        $query = LeaveRequest::with(['leaveType', 'coveringEmployee.user', 'employee.user', 'employee.department'])
            ->where('employee_id', $employee->id ?? 1);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('req_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('project_client_name', 'like', "%{$search}%");
            });
        }

        if ($leaveTypeId) {
            $query->where('leave_type_id', $leaveTypeId);
        }

        if ($year) {
            $query->whereYear('start_date', $year);
        }

        if ($month) {
            $query->whereMonth('start_date', $month);
        }

        if ($status === 'pending') {
            $query->whereIn('status', ['Pending', 'Pending Covering Approval', 'Manager Approved (Pending HR)', 'Step 2: Pending HR Admin']);
        } elseif ($status === 'approved') {
            $query->where('status', 'Approved');
        } elseif ($status === 'rejected') {
            $query->whereIn('status', ['Rejected', 'Canceled']);
        } elseif ($status === 'half_day') {
            $query->where('is_half_day', true);
        } elseif ($status === 'short_leave') {
            $query->where('is_short_leave', true);
        }

        $leaveRequests = $query->latest()->paginate(15)->withQueryString();

        // Leave Balances Summary
        $leaveBalances = EmployeeLeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id ?? 1)
            ->where('year', $year)
            ->get();

        // KPI Counts
        $allRequests = LeaveRequest::where('employee_id', $employee->id ?? 1)->get();
        $counts = [
            'all' => $allRequests->count(),
            'pending' => $allRequests->whereIn('status', ['Pending', 'Pending Covering Approval', 'Manager Approved (Pending HR)', 'Step 2: Pending HR Admin'])->count(),
            'approved' => $allRequests->where('status', 'Approved')->count(),
            'rejected' => $allRequests->whereIn('status', ['Rejected', 'Canceled'])->count(),
            'half_day' => $allRequests->where('is_half_day', true)->count(),
            'short_leave' => $allRequests->where('is_short_leave', true)->count(),
            'total_days_used' => $allRequests->where('status', 'Approved')->sum('duration'),
        ];

        $leaveTypes = LeaveType::all();
        $monthsList = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        $yearsList = range(2024, 2030);

        return view('pages.my_leaves', compact(
            'employee',
            'leaveRequests',
            'leaveBalances',
            'counts',
            'leaveTypes',
            'monthsList',
            'yearsList',
            'status',
            'search',
            'leaveTypeId',
            'year',
            'month'
        ));
    }

    public function cancel(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->manager_status === 'Approved' || in_array($leaveRequest->status, ['Manager Approved (Pending HR)', 'Approved', 'Rejected', 'Canceled'])) {
            return redirect()->back()->withErrors(['error' => 'Leave request cannot be canceled after manager approval has been granted.']);
        }

        $leaveRequest->update([
            'status' => 'Canceled',
            'hr_status' => 'Canceled',
        ]);

        // Refund deducted days back to available balance
        $this->leavePolicyService->refundLeaveBalance($leaveRequest);

        // Dispatch notification
        \App\Services\NotificationService::notifyLeaveCanceled($leaveRequest);

        return redirect()->back()->with('success', 'Leave request canceled successfully! Deducted days have been refunded to your balance.');
    }
}
