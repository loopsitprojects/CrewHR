<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\Payslip;
use Modules\Payroll\Models\EmployeeLoan;
use Modules\Payroll\Models\LoanType;
use Modules\Payroll\Models\SalaryAdvance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollController extends Controller
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
            abort(403, 'Access Denied: Only HR Leads and Super Admins can manage company payroll.');
        }
    }

    public function index(Request $request)
    {
        $role = $this->getActiveRole();

        // If regular employee, route to My Payslips view
        if ($role === 'Employee') {
            return $this->myPayslips($request);
        }

        $now = Carbon::now('Asia/Colombo');
        $month = (int) $request->get('month', $now->month);
        $year = (int) $request->get('year', $now->year);
        $category = $request->get('staff_category', 'All');
        $paymentMethodFilter = $request->get('payment_method', 'All');

        if ($month < 1 || $month > 12) $month = $now->month;
        if ($year < 2000 || $year > 2100) $year = $now->year;

        $monthName = Carbon::createFromDate($year, $month, 1)->format('F');
        $cycleName = "{$monthName} {$year} Payroll";

        $payroll = Payroll::with(['payslips.employee.user', 'payslips.employee.department', 'payslips.employee.designation'])
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $employeesQuery = Employee::with(['user', 'department', 'designation']);
        if ($category !== 'All') {
            $employeesQuery->where('staff_category', $category);
        }
        if ($paymentMethodFilter !== 'All') {
            $employeesQuery->where('payment_method', $paymentMethodFilter);
        }
        $employees = $employeesQuery->get();

        // Fetch active loans & advances for modal dropdowns
        $loanTypes = LoanType::where('status', 'Active')->get();
        $allActiveLoans = EmployeeLoan::with('employee.user')->where('status', 'Active')->get();
        $allPendingAdvances = SalaryAdvance::with('employee.user')->where('status', 'Pending')->get();

        // Calculate live totals from DB or live preview
        if ($payroll && $payroll->payslips->count() > 0) {
            $payslipsQuery = $payroll->payslips();
            if ($category !== 'All') {
                $payslipsQuery->where('staff_category', $category);
            }
            if ($paymentMethodFilter !== 'All') {
                $payslipsQuery->where('payment_method', $paymentMethodFilter);
            }
            $payslips = $payslipsQuery->get();

            $totalGross = $payslips->sum('gross_salary');
            $totalEpfEmployer = $payslips->sum('epf_employer');
            $totalEtfEmployer = $payslips->sum('etf_employer');
            $totalNetPay = $payslips->sum('net_salary');
        } else {
            $payslips = collect();
            $totalGross = 0;
            $totalEpfEmployer = 0;
            $totalEtfEmployer = 0;
            $totalNetPay = 0;

            foreach ($employees as $emp) {
                $basic = $emp->basic_salary ?: 180000;
                $allowances = ($emp->fixed_allowance ?: 30000) + ($emp->other_allowance ?: 20000);
                $gross = $basic + $allowances;
                $epf8 = $basic * 0.08;
                $epf12 = $basic * 0.12;
                $etf3 = $basic * 0.03;
                $apit = $emp->apit_tax ?: 10000;
                $net = $gross - $epf8 - $apit;

                $totalGross += $gross;
                $totalEpfEmployer += $epf12;
                $totalEtfEmployer += $etf3;
                $totalNetPay += $net;
            }
        }

        $monthsList = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        $yearsList = range(2024, 2030);
        $categoriesList = ['All', 'Executive', 'Non-Executive', 'Shift Staff', 'Management', 'Contract'];
        $paymentMethodsList = ['All', 'Bank Transfer', 'Cash', 'Cheque'];

        return view('payroll::index', compact(
            'payroll',
            'employees',
            'payslips',
            'month',
            'year',
            'category',
            'paymentMethodFilter',
            'monthName',
            'cycleName',
            'totalGross',
            'totalEpfEmployer',
            'totalEtfEmployer',
            'totalNetPay',
            'monthsList',
            'yearsList',
            'categoriesList',
            'paymentMethodsList',
            'loanTypes',
            'allActiveLoans',
            'allPendingAdvances'
        ));
    }

    public function process(Request $request)
    {
        $this->authorizeAdminOrHr();

        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2000,2100',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;

        $monthName = Carbon::createFromDate($year, $month, 1)->format('F');
        $cycleName = "{$monthName} {$year} Payroll";

        DB::transaction(function () use ($month, $year, $cycleName, $request) {
            $payroll = Payroll::firstOrCreate(
                ['month' => $month, 'year' => $year],
                ['cycle_name' => $cycleName, 'status' => 'Processed', 'processed_by_user_id' => auth()->id()]
            );

            $employees = Employee::with('user')->get();

            $totBasic = 0;
            $totAllowances = 0;
            $totGross = 0;
            $totEpfEmp = 0;
            $totEpfEmployer = 0;
            $totEtfEmployer = 0;
            $totApit = 0;
            $totNet = 0;

            foreach ($employees as $emp) {
                $basic = (float) ($emp->basic_salary ?: 180000);
                $fixed = (float) ($emp->fixed_allowance ?: 30000);
                $other = (float) ($emp->other_allowance ?: 20000);

                // Attendance Payment Rules Input (if provided per employee)
                $otHours = (float) ($request->input("ot_hours.{$emp->id}", rand(0, 15)));
                $otAmount = round(($basic / 200) * $otHours * 1.5, 2);

                $shiftAllowance = (float) ($request->input("shift_allowance.{$emp->id}", str_contains($emp->staff_category ?? '', 'Shift') ? 15000 : 0));
                $performanceIncentive = (float) ($request->input("performance_incentive.{$emp->id}", 0));

                $totalAllowances = $fixed + $other + $otAmount + $shiftAllowance + $performanceIncentive;
                $gross = $basic + $totalAllowances;

                // Statutory Deductions
                $epf8 = round($basic * 0.08, 2);
                $epf12 = round($basic * 0.12, 2);
                $etf3 = round($basic * 0.03, 2);
                $apit = (float) ($emp->apit_tax ?: 10000);

                // Attendance No-Pay Deduction Rule
                $noPayDays = (float) ($request->input("no_pay_days.{$emp->id}", 0));
                $noPayDeduction = round(($basic / 30) * $noPayDays, 2);

                // Loan Auto-Deduction
                $activeLoan = EmployeeLoan::where('employee_id', $emp->id)->where('status', 'Active')->first();
                $loanInstallment = 0;
                $currentPayslip = null;

                if ($activeLoan && $activeLoan->remaining_balance > 0) {
                    $loanInstallment = min($activeLoan->monthly_installment, $activeLoan->remaining_balance);
                }

                // Salary Advance Auto-Deduction
                $advance = SalaryAdvance::where('employee_id', $emp->id)->where('status', 'Pending')->first();
                $salaryAdvanceDeduction = 0;
                if ($advance) {
                    $salaryAdvanceDeduction = $advance->amount;
                    $advance->update(['status' => 'Deducted']);
                }

                $totalDeductions = $epf8 + $apit + $noPayDeduction + $loanInstallment + $salaryAdvanceDeduction;
                $net = max(0, $gross - $totalDeductions);

                $psRecord = Payslip::updateOrCreate(
                    ['payroll_id' => $payroll->id, 'employee_id' => $emp->id],
                    [
                        'month' => $month,
                        'year' => $year,
                        'staff_category' => $emp->staff_category ?? 'Executive',
                        'payment_method' => $emp->payment_method ?? 'Bank Transfer',
                        'basic_salary' => $basic,
                        'fixed_allowance' => $fixed,
                        'other_allowance' => $other,
                        'ot_hours' => $otHours,
                        'ot_amount' => $otAmount,
                        'shift_allowance' => $shiftAllowance,
                        'performance_incentive' => $performanceIncentive,
                        'gross_salary' => $gross,
                        'no_pay_days' => $noPayDays,
                        'no_pay_deduction' => $noPayDeduction,
                        'epf_employee' => $epf8,
                        'epf_employer' => $epf12,
                        'etf_employer' => $etf3,
                        'apit_tax' => $apit,
                        'loan_installment' => $loanInstallment,
                        'salary_advance' => $salaryAdvanceDeduction,
                        'other_deductions' => $noPayDeduction + $loanInstallment + $salaryAdvanceDeduction,
                        'net_salary' => $net,
                        'status' => 'Processed',
                    ]
                );

                if ($activeLoan && $loanInstallment > 0) {
                    $newRemaining = max(0, $activeLoan->remaining_balance - $loanInstallment);
                    $newTotalPaid = $activeLoan->total_paid + $loanInstallment;
                    $loanStatus = $newRemaining <= 0 ? 'Completed' : 'Active';

                    \Modules\Payroll\Models\LoanRepayment::create([
                        'employee_loan_id' => $activeLoan->id,
                        'payslip_id' => $psRecord->id,
                        'amount_paid' => $loanInstallment,
                        'principal_paid' => $loanInstallment,
                        'remaining_balance_after' => $newRemaining,
                        'repayment_type' => 'Payroll Auto-Deduction',
                        'paid_date' => now(),
                        'notes' => "Monthly payroll deduction ({$monthName} {$year})",
                    ]);

                    $activeLoan->update([
                        'remaining_balance' => $newRemaining,
                        'total_paid' => $newTotalPaid,
                        'status' => $loanStatus,
                    ]);
                }

                $totBasic += $basic;
                $totAllowances += $totalAllowances;
                $totGross += $gross;
                $totEpfEmp += $epf8;
                $totEpfEmployer += $epf12;
                $totEtfEmployer += $etf3;
                $totApit += $apit;
                $totNet += $net;
            }

            $payroll->update([
                'cycle_name' => $cycleName,
                'status' => 'Processed',
                'total_basic' => $totBasic,
                'total_allowances' => $totAllowances,
                'total_gross' => $totGross,
                'total_epf_employee' => $totEpfEmp,
                'total_epf_employer' => $totEpfEmployer,
                'total_etf_employer' => $totEtfEmployer,
                'total_apit_tax' => $totApit,
                'total_net_pay' => $totNet,
                'processed_at' => now(),
            ]);
        });

        return redirect()->route('payroll.index', ['month' => $month, 'year' => $year])
            ->with('success', "Payroll for {$monthName} {$year} processed successfully with statutory, OT, No-Pay, and Loan deductions!");
    }

    public function storeLoan(Request $request)
    {
        $this->authorizeAdminOrHr();

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'loan_title' => 'required|string|max:255',
            'principal_amount' => 'required|numeric|min:1',
            'monthly_installment' => 'required|numeric|min:1',
        ]);

        EmployeeLoan::create([
            'employee_id' => $request->employee_id,
            'loan_title' => $request->loan_title,
            'principal_amount' => $request->principal_amount,
            'monthly_installment' => $request->monthly_installment,
            'remaining_balance' => $request->principal_amount,
            'status' => 'Active',
            'start_date' => now(),
        ]);

        return redirect()->back()->with('success', 'Staff loan recorded successfully!');
    }

    public function storeAdvance(Request $request)
    {
        $this->authorizeAdminOrHr();

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:1',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2000,2100',
            'reason' => 'nullable|string|max:255',
        ]);

        SalaryAdvance::create([
            'employee_id' => $request->employee_id,
            'amount' => $request->amount,
            'month' => $request->month,
            'year' => $request->year,
            'reason' => $request->reason,
            'status' => 'Pending',
        ]);

        return redirect()->back()->with('success', 'Salary advance approved & queued for next payroll deduction!');
    }

    public function exportBankAdvice($id)
    {
        $this->authorizeAdminOrHr();

        $payroll = Payroll::with(['payslips.employee.user', 'payslips.employee.department'])->findOrFail($id);

        $filename = "Bank_Advice_Report_{$payroll->month}_{$payroll->year}.csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($payroll) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Employee ID', 'Employee Name', 'Staff Category', 'EPF No', 'Bank Name', 'Branch', 'Account Number', 'Net Pay (LKR)']);

            foreach ($payroll->payslips as $ps) {
                if ($ps->payment_method === 'Cash') continue;

                $emp = $ps->employee;
                fputcsv($file, [
                    $emp->employee_id_number ?? "EMP-{$emp->id}",
                    $emp->user->name ?? 'Employee',
                    $ps->staff_category ?? 'Executive',
                    $emp->epf_registration_no ?? 'N/A',
                    $emp->bank_name ?? 'Commercial Bank PLC',
                    $emp->bank_branch ?? 'Head Office',
                    $emp->account_number ?? '0010098234',
                    number_format($ps->net_salary, 2, '.', ''),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportCashDenomination($id)
    {
        $this->authorizeAdminOrHr();

        $payroll = Payroll::with(['payslips.employee.user'])->findOrFail($id);
        $cashPayslips = $payroll->payslips->where('payment_method', 'Cash');

        $filename = "Cash_Denomination_Report_{$payroll->month}_{$payroll->year}.csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($cashPayslips) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Employee ID', 'Employee Name', 'Net Pay (LKR)', '5000 Notes', '1000 Notes', '500 Notes', '100 Notes', '50 Notes', '20 Notes', 'Coins/Balance']);

            $tot5000 = 0; $tot1000 = 0; $tot500 = 0; $tot100 = 0; $tot50 = 0; $tot20 = 0; $totCoins = 0; $totPay = 0;

            foreach ($cashPayslips as $ps) {
                $rem = (int) round($ps->net_salary);
                $n5000 = intdiv($rem, 5000); $rem %= 5000;
                $n1000 = intdiv($rem, 1000); $rem %= 1000;
                $n500 = intdiv($rem, 500);   $rem %= 500;
                $n100 = intdiv($rem, 100);   $rem %= 100;
                $n50 = intdiv($rem, 50);     $rem %= 50;
                $n20 = intdiv($rem, 20);     $rem %= 20;
                $coins = $rem;

                $tot5000 += $n5000; $tot1000 += $n1000; $tot500 += $n500;
                $tot100 += $n100; $tot50 += $n50; $tot20 += $n20; $totCoins += $coins;
                $totPay += $ps->net_salary;

                fputcsv($file, [
                    $ps->employee->employee_id_number ?? "EMP-{$ps->employee_id}",
                    $ps->employee->user->name ?? 'Employee',
                    number_format($ps->net_salary, 2, '.', ''),
                    $n5000, $n1000, $n500, $n100, $n50, $n20, $coins
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['TOTAL CASH REQUIRED', '', number_format($totPay, 2, '.', ''), $tot5000, $tot1000, $tot500, $tot100, $tot50, $tot20, $totCoins]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function myPayslips(Request $request)
    {
        $user = auth()->user();
        $employee = $user ? Employee::where('user_id', $user->id)->first() : null;

        $payslips = $employee ? Payslip::with('payroll')
            ->where('employee_id', $employee->id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get() : collect();

        return view('payroll::my_payslips', compact('employee', 'payslips'));
    }
}
