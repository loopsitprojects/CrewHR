<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Payroll\Models\LoanType;
use Modules\Payroll\Models\EmployeeLoan;
use Modules\Payroll\Models\LoanRepayment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoanController extends Controller
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
            abort(403, 'Access Denied: Only HR Leads and Super Admins can manage loan approvals and types.');
        }
    }

    public function index(Request $request)
    {
        $role = $this->getActiveRole();
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;

        $query = EmployeeLoan::with(['employee.user', 'employee.department', 'loanType', 'repayments']);

        // Employees only see their own loans
        if ($role === 'Employee' && $activeEmp) {
            $query->where('employee_id', $activeEmp->id);
        }

        $allLoans = $query->latest('applied_at')->get();

        $activeLoans = $allLoans->whereIn('status', ['Active', 'Completed']);
        $pendingLoans = $allLoans->where('status', 'Pending');
        $loanTypes = LoanType::all();
        $employees = Employee::with('user')->get();

        // Calculate KPI Metrics
        $totalActiveCount = $activeLoans->where('status', 'Active')->count();
        $totalOutstanding = $activeLoans->where('status', 'Active')->sum('remaining_balance');
        $totalIssuedAmount = $allLoans->whereIn('status', ['Active', 'Completed'])->sum('principal_amount');
        $pendingCount = $pendingLoans->count();

        // Transaction Repayment Ledger
        $repaymentsQuery = LoanRepayment::with(['loan.employee.user', 'loan.loanType']);
        if ($role === 'Employee' && $activeEmp) {
            $repaymentsQuery->whereHas('loan', function ($q) use ($activeEmp) {
                $q->where('employee_id', $activeEmp->id);
            });
        }
        $recentRepayments = $repaymentsQuery->latest()->take(30)->get();

        return view('payroll::loans.index', compact(
            'allLoans',
            'activeLoans',
            'pendingLoans',
            'loanTypes',
            'employees',
            'totalActiveCount',
            'totalOutstanding',
            'totalIssuedAmount',
            'pendingCount',
            'recentRepayments',
            'role',
            'activeEmp'
        ));
    }

    public function apply(Request $request)
    {
        $role = $this->getActiveRole();
        $user = auth()->user();
        $activeEmp = $user ? Employee::where('user_id', $user->id)->first() : null;

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'loan_type_id' => 'required|exists:loan_types,id',
            'principal_amount' => 'required|numeric|min:1000',
            'repayment_months' => 'required|integer|min:1|max:120',
            'purpose' => 'nullable|string|max:500',
        ]);

        $loanType = LoanType::findOrFail($request->loan_type_id);
        $principal = (float) $request->principal_amount;
        $months = (int) $request->repayment_months;

        if ($principal > $loanType->max_amount) {
            return redirect()->back()->withErrors([
                'principal_amount' => "Requested amount exceeds maximum allowed for {$loanType->name} (Max: Rs. " . number_format($loanType->max_amount) . ")."
            ]);
        }

        $monthlyInstallment = round($principal / $months, 2);
        $loanNumber = 'LOAN-' . rand(10000, 99999);

        // HR & Super Admins can auto-approve upon submission if selected
        $autoApprove = in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin']) && $request->has('auto_approve');
        $status = $autoApprove ? 'Active' : 'Pending';

        $loan = EmployeeLoan::create([
            'loan_number' => $loanNumber,
            'employee_id' => $request->employee_id,
            'loan_type_id' => $loanType->id,
            'loan_title' => $loanType->name,
            'principal_amount' => $principal,
            'interest_rate' => $loanType->interest_rate_annual,
            'repayment_months' => $months,
            'monthly_installment' => $monthlyInstallment,
            'remaining_balance' => $principal,
            'total_paid' => 0,
            'purpose' => $request->purpose,
            'status' => $status,
            'applied_at' => now(),
            'approved_at' => $autoApprove ? now() : null,
            'approved_by_user_id' => $autoApprove ? auth()->id() : null,
        ]);

        $msg = $autoApprove ? "Loan {$loanNumber} issued and activated successfully!" : "Loan application {$loanNumber} submitted and queued for HR approval.";

        return redirect()->route('payroll.loans.index')->with('success', $msg);
    }

    public function approve($id)
    {
        $this->authorizeAdminOrHr();

        $loan = EmployeeLoan::findOrFail($id);
        $loan->update([
            'status' => 'Active',
            'approved_at' => now(),
            'approved_by_user_id' => auth()->id(),
        ]);

        return redirect()->route('payroll.loans.index')->with('success', "Loan {$loan->loan_number} approved and activated!");
    }

    public function reject($id)
    {
        $this->authorizeAdminOrHr();

        $loan = EmployeeLoan::findOrFail($id);
        $loan->update(['status' => 'Rejected']);

        return redirect()->route('payroll.loans.index')->with('success', "Loan {$loan->loan_number} application rejected.");
    }

    public function storeType(Request $request)
    {
        $this->authorizeAdminOrHr();

        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:loan_types,code',
            'max_amount' => 'required|numeric|min:1',
            'interest_rate_annual' => 'required|numeric|min:0',
            'max_repayment_months' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        LoanType::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'max_amount' => $request->max_amount,
            'interest_rate_annual' => $request->interest_rate_annual,
            'max_repayment_months' => $request->max_repayment_months,
            'description' => $request->description,
            'status' => 'Active',
        ]);

        return redirect()->back()->with('success', "Loan Type '{$request->name}' created successfully!");
    }

    public function recordPayment(Request $request, $id)
    {
        $this->authorizeAdminOrHr();

        $request->validate([
            'amount_paid' => 'required|numeric|min:1',
            'repayment_type' => 'required|string',
            'notes' => 'nullable|string|max:255',
        ]);

        $loan = EmployeeLoan::findOrFail($id);
        $amount = (float) $request->amount_paid;

        if ($amount > $loan->remaining_balance) {
            $amount = $loan->remaining_balance;
        }

        $newRemaining = max(0, $loan->remaining_balance - $amount);
        $newTotalPaid = $loan->total_paid + $amount;
        $status = $newRemaining <= 0 ? 'Completed' : 'Active';

        DB::transaction(function () use ($loan, $amount, $newRemaining, $newTotalPaid, $status, $request) {
            LoanRepayment::create([
                'employee_loan_id' => $loan->id,
                'amount_paid' => $amount,
                'principal_paid' => $amount,
                'remaining_balance_after' => $newRemaining,
                'repayment_type' => $request->repayment_type,
                'paid_date' => now(),
                'notes' => $request->notes ?? 'Manual Repayment',
            ]);

            $loan->update([
                'remaining_balance' => $newRemaining,
                'total_paid' => $newTotalPaid,
                'status' => $status,
            ]);
        });

        return redirect()->back()->with('success', "Manual payment of Rs. " . number_format($amount, 2) . " recorded for Loan {$loan->loan_number}.");
    }

    public function exportReport()
    {
        $this->authorizeAdminOrHr();

        $loans = EmployeeLoan::with(['employee.user', 'employee.department', 'loanType'])->latest()->get();

        $filename = "Staff_Loans_Report_" . date('Y_m_d') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($loans) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Loan No', 'Employee Name', 'Department', 'Loan Type', 'Principal Amount (LKR)', 'Monthly Installment (LKR)', 'Total Paid (LKR)', 'Remaining Balance (LKR)', 'Status', 'Applied Date']);

            foreach ($loans as $l) {
                fputcsv($file, [
                    $l->loan_number ?? "LOAN-{$l->id}",
                    $l->employee->user->name ?? 'Employee',
                    $l->employee->department->name ?? 'General',
                    $l->loanType->name ?? $l->loan_title,
                    number_format($l->principal_amount, 2, '.', ''),
                    number_format($l->monthly_installment, 2, '.', ''),
                    number_format($l->total_paid, 2, '.', ''),
                    number_format($l->remaining_balance, 2, '.', ''),
                    $l->status,
                    Carbon::parse($l->applied_at)->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
