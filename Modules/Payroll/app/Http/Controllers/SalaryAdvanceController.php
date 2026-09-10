<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Payroll\Models\SalaryAdvance;
use Carbon\Carbon;

class SalaryAdvanceController extends Controller
{
    private function getActiveRole()
    {
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;
        return session('current_role', $activeEmp->system_role ?? 'Employee');
    }

    private function authorizeAdminOrHr()
    {
        $role = $this->getActiveRole();
        if (!in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin'])) {
            abort(403, 'Access Denied: Only HR Leads and Super Admins can manage salary advance approvals.');
        }
    }

    public function index(Request $request)
    {
        $role = $this->getActiveRole();
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;

        $month = (int) $request->get('month', Carbon::now()->month);
        $year = (int) $request->get('year', Carbon::now()->year);
        $statusFilter = $request->get('status', 'All');
        $deptFilter = $request->get('department_id');

        $query = SalaryAdvance::with(['employee.user', 'employee.department', 'employee.designation', 'approver'])
            ->where('year', $year)
            ->when($month, fn($q) => $q->where('month', $month));

        if ($role === 'Employee' && $activeEmp) {
            $query->where('employee_id', $activeEmp->id);
        } elseif ($deptFilter) {
            $query->whereHas('employee', fn($e) => $e->where('department_id', $deptFilter));
        }

        if ($statusFilter !== 'All') {
            $query->where('status', $statusFilter);
        }

        $advances = $query->latest('id')->get();

        // Summary KPI Metrics
        $totalApprovedAmount = $advances->whereIn('status', ['Approved', 'Deducted'])->sum('amount');
        $pendingCount = $advances->where('status', 'Pending')->sum('amount');
        $pendingRequestsCount = $advances->where('status', 'Pending')->count();
        $deductedInPayroll = $advances->where('status', 'Deducted')->sum('amount');

        $employees = Employee::with(['user', 'department', 'designation'])->get();
        $departments = Department::all();

        return view('payroll::advances.index', [
            'advances' => $advances,
            'role' => $role,
            'activeEmp' => $activeEmp,
            'employees' => $employees,
            'departments' => $departments,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'statusFilter' => $statusFilter,
            'deptFilter' => $deptFilter,
            'totalApprovedAmount' => $totalApprovedAmount,
            'pendingAmount' => $pendingCount,
            'pendingRequestsCount' => $pendingRequestsCount,
            'deductedInPayroll' => $deductedInPayroll,
        ]);
    }

    public function apply(Request $request)
    {
        $role = $this->getActiveRole();
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;

        $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'amount' => 'required|numeric|min:1000|max:200000',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2035',
            'reason' => 'required|string|max:500',
        ]);

        $employeeId = $request->employee_id;
        if ($role === 'Employee' || empty($employeeId)) {
            $employeeId = $activeEmp?->id;
        }

        if (!$employeeId) {
            return back()->with('error', 'Unable to determine employee profile.');
        }

        $advanceNumber = 'ADV-' . date('ym') . '-' . strtoupper(substr(uniqid(), -4));

        SalaryAdvance::create([
            'advance_number' => $advanceNumber,
            'employee_id' => $employeeId,
            'amount' => $request->amount,
            'month' => $request->month,
            'year' => $request->year,
            'reason' => $request->reason,
            'requested_date' => now()->toDateString(),
            'status' => in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']) ? 'Approved' : 'Pending',
            'approved_at' => in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']) ? now() : null,
            'approved_by_user_id' => in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']) ? auth()->id() : null,
        ]);

        return back()->with('success', "Salary advance application ({$advanceNumber}) submitted successfully!");
    }

    public function approve($id)
    {
        $this->authorizeAdminOrHr();

        $advance = SalaryAdvance::findOrFail($id);
        $advance->update([
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by_user_id' => auth()->id(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', "Salary advance {$advance->advance_number} approved and queued for payroll deduction!");
    }

    public function reject(Request $request, $id)
    {
        $this->authorizeAdminOrHr();

        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $advance = SalaryAdvance::findOrFail($id);
        $advance->update([
            'status' => 'Rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ]);

        return back()->with('success', "Salary advance {$advance->advance_number} has been rejected.");
    }

    public function cancel($id)
    {
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;

        $advance = SalaryAdvance::findOrFail($id);
        if ($advance->employee_id !== $activeEmp?->id && !in_array($this->getActiveRole(), ['HR Lead', 'Super (Admin)', 'Super Admin'])) {
            abort(403);
        }

        if ($advance->status !== 'Pending') {
            return back()->with('error', 'Only pending advance requests can be cancelled.');
        }

        $advance->update(['status' => 'Cancelled']);

        return back()->with('success', "Salary advance {$advance->advance_number} cancelled.");
    }

    public function export(Request $request)
    {
        $this->authorizeAdminOrHr();

        $month = (int) $request->get('month', Carbon::now()->month);
        $year = (int) $request->get('year', Carbon::now()->year);
        $status = $request->get('status', 'All');

        $query = SalaryAdvance::with(['employee.user', 'employee.department', 'employee.designation', 'approver'])
            ->where('year', $year)
            ->when($month, fn($q) => $q->where('month', $month));

        if ($status !== 'All') {
            $query->where('status', $status);
        }

        $advances = $query->latest('id')->get();

        $filename = "Loops_HR_Salary_Advances_{$year}_{$month}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($advances) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Advance Number', 'Employee Name', 'EMP ID', 'Department', 'Designation', 'Cycle Month', 'Cycle Year', 'Amount (LKR)', 'Status', 'Reason', 'Approved By', 'Approved At']);

            foreach ($advances as $adv) {
                fputcsv($handle, [
                    $adv->advance_number ?? ('ADV-' . $adv->id),
                    $adv->employee?->user?->name ?? 'N/A',
                    $adv->employee?->employee_id_number ?? 'N/A',
                    $adv->employee?->department?->name ?? 'N/A',
                    $adv->employee?->designation?->name ?? 'N/A',
                    $adv->month,
                    $adv->year,
                    $adv->amount,
                    $adv->status,
                    $adv->reason,
                    $adv->approver?->name ?? 'N/A',
                    $adv->approved_at ? $adv->approved_at->format('Y-m-d H:i') : 'N/A',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
