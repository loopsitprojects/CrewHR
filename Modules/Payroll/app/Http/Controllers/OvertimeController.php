<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Payroll\Models\OvertimeRecord;
use Carbon\Carbon;

class OvertimeController extends Controller
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
            abort(403, 'Access Denied: Only HR Leads and Super Admins can perform overtime approval actions.');
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

        $query = OvertimeRecord::with(['employee.user', 'employee.department', 'employee.designation', 'approver'])
            ->whereYear('ot_date', $year)
            ->when($month, fn($q) => $q->whereMonth('ot_date', $month));

        if ($role === 'Employee' && $activeEmp) {
            $query->where('employee_id', $activeEmp->id);
        } elseif ($deptFilter) {
            $query->whereHas('employee', fn($e) => $e->where('department_id', $deptFilter));
        }

        if ($statusFilter !== 'All') {
            $query->where('status', $statusFilter);
        }

        $records = $query->latest('ot_date')->get();

        // Summary KPI Metrics
        $totalHoursApproved = $records->where('status', 'Approved')->sum('hours');
        $totalAmountApproved = $records->where('status', 'Approved')->sum('estimated_amount');
        $pendingRequestsCount = $records->where('status', 'Pending')->count();
        $totalClaimCount = $records->count();

        $employees = Employee::with(['user', 'department', 'designation'])->get();
        $departments = \Modules\Employee\Models\Department::all();

        return view('payroll::overtime.index', [
            'records' => $records,
            'role' => $role,
            'activeEmp' => $activeEmp,
            'employees' => $employees,
            'departments' => $departments,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'statusFilter' => $statusFilter,
            'deptFilter' => $deptFilter,
            'totalHoursApproved' => $totalHoursApproved,
            'totalAmountApproved' => $totalAmountApproved,
            'pendingRequestsCount' => $pendingRequestsCount,
            'totalClaimCount' => $totalClaimCount,
        ]);
    }

    public function store(Request $request)
    {
        $role = $this->getActiveRole();
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;

        $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'ot_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'hours' => 'required|numeric|min:0.5|max:24',
            'rate_multiplier_type' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        $employeeId = $request->employee_id;
        if ($role === 'Employee' || empty($employeeId)) {
            $employeeId = $activeEmp?->id;
        }

        if (!$employeeId) {
            return back()->with('error', 'Unable to determine employee profile.');
        }

        $emp = Employee::find($employeeId);
        $basic = (float) ($emp->basic_salary ?: 180000);
        $hourlyRate = round($basic / 200, 2); // Sri Lanka standard: Basic / 200 hours

        $multiplier = match ($request->rate_multiplier_type) {
            'Double (2.0x)' => 2.0,
            'Holiday (2.5x)' => 2.5,
            default => 1.5,
        };

        $hours = (float) $request->hours;
        $estimatedAmount = round($hourlyRate * $hours * $multiplier, 2);

        $otNumber = 'OT-' . date('ym') . '-' . strtoupper(substr(uniqid(), -4));

        OvertimeRecord::create([
            'ot_number' => $otNumber,
            'employee_id' => $employeeId,
            'ot_date' => $request->ot_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'hours' => $hours,
            'rate_multiplier_type' => $request->rate_multiplier_type,
            'multiplier' => $multiplier,
            'hourly_rate' => $hourlyRate,
            'estimated_amount' => $estimatedAmount,
            'reason' => $request->reason,
            'status' => in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']) ? 'Approved' : 'Pending',
            'approved_at' => in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']) ? now() : null,
            'approved_by_user_id' => in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']) ? auth()->id() : null,
        ]);

        return back()->with('success', "Overtime claim ({$otNumber}) submitted successfully!");
    }

    public function approve($id)
    {
        $this->authorizeAdminOrHr();

        $ot = OvertimeRecord::findOrFail($id);
        $ot->update([
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by_user_id' => auth()->id(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', "Overtime claim {$ot->ot_number} approved successfully!");
    }

    public function reject(Request $request, $id)
    {
        $this->authorizeAdminOrHr();

        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $ot = OvertimeRecord::findOrFail($id);
        $ot->update([
            'status' => 'Rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ]);

        return back()->with('success', "Overtime claim {$ot->ot_number} rejected.");
    }

    public function cancel($id)
    {
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;

        $ot = OvertimeRecord::findOrFail($id);
        if ($ot->employee_id !== $activeEmp?->id && !in_array($this->getActiveRole(), ['HR Lead', 'Super (Admin)', 'Super Admin'])) {
            abort(403);
        }

        if ($ot->status !== 'Pending') {
            return back()->with('error', 'Only pending overtime claims can be cancelled.');
        }

        $ot->update(['status' => 'Cancelled']);

        return back()->with('success', "Overtime claim {$ot->ot_number} cancelled.");
    }

    public function export(Request $request)
    {
        $this->authorizeAdminOrHr();

        $month = (int) $request->get('month', Carbon::now()->month);
        $year = (int) $request->get('year', Carbon::now()->year);
        $status = $request->get('status', 'All');

        $query = OvertimeRecord::with(['employee.user', 'employee.department', 'employee.designation', 'approver'])
            ->whereYear('ot_date', $year)
            ->when($month, fn($q) => $q->whereMonth('ot_date', $month));

        if ($status !== 'All') {
            $query->where('status', $status);
        }

        $records = $query->latest('ot_date')->get();

        $filename = "Loops_HR_Overtime_Report_{$year}_{$month}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['OT Number', 'Employee Name', 'EMP ID', 'Department', 'Designation', 'OT Date', 'Start Time', 'End Time', 'Hours', 'Rate Multiplier', 'Base Rate/Hr (LKR)', 'Estimated Amount (LKR)', 'Status', 'Reason', 'Approved By', 'Approved At']);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->ot_number,
                    $r->employee?->user?->name ?? 'N/A',
                    $r->employee?->employee_id_number ?? 'N/A',
                    $r->employee?->department?->name ?? 'N/A',
                    $r->employee?->designation?->name ?? 'N/A',
                    $r->ot_date->format('Y-m-d'),
                    $r->start_time,
                    $r->end_time,
                    $r->hours,
                    $r->rate_multiplier_type,
                    $r->hourly_rate,
                    $r->estimated_amount,
                    $r->status,
                    $r->reason,
                    $r->approver?->name ?? 'N/A',
                    $r->approved_at ? $r->approved_at->format('Y-m-d H:i') : 'N/A',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
