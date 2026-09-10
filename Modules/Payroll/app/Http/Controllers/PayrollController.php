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
use Modules\Payroll\Models\LoanRepayment;
use Modules\Payroll\Services\PayrollCalculationService;
use Modules\Payroll\Services\PayrollAccountingService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollController extends Controller
{
    protected PayrollCalculationService $calcService;
    protected PayrollAccountingService $accountingService;

    public function __construct(PayrollCalculationService $calcService, PayrollAccountingService $accountingService)
    {
        $this->calcService = $calcService;
        $this->accountingService = $accountingService;
    }

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

        // If not HR or Super Admin, route directly to My Payslips view
        if (!in_array($role, ['HR Lead', 'Super (Admin)', 'Super Admin'])) {
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

        $journalData = null;

        // Calculate live totals from DB or live preview
        if ($payroll && $payroll->payslips->count() > 0) {
            $payslipsQuery = $payroll->payslips();
            if ($category !== 'All') {
                $payslipsQuery->where('staff_category', $category);
            }
            if ($paymentMethodFilter !== 'All') {
                $payslipsQuery->where('payment_method', $paymentMethodFilter);
            }
            $payslips = $payslipsQuery->with(['employee.user', 'employee.department', 'employee.designation'])->get();

            $totalGross = (float) $payslips->sum('gross_salary');
            $totalEpfQualifying = (float) $payslips->sum('total_for_epf');
            $totalEpfEmployee = (float) $payslips->sum('epf_employee');
            $totalEpfEmployer = (float) $payslips->sum('epf_employer');
            $totalEtfEmployer = (float) $payslips->sum('etf_employer');
            $totalApit = (float) $payslips->sum('apit_tax');
            $totalNetPay = (float) $payslips->sum('net_salary');

            // Direct vs Indirect Cost
            $directPayslips = $payslips->filter(fn($p) => ($p->cost_classification ?? 'Direct') === 'Direct');
            $indirectPayslips = $payslips->filter(fn($p) => ($p->cost_classification ?? 'Direct') === 'Indirect');
            $totalDirectSalaries = (float) $directPayslips->sum('gross_salary');
            $totalIndirectSalaries = (float) $indirectPayslips->sum('gross_salary');

            // Generate double-entry journal sheet
            $journalData = $this->accountingService->generateJournalEntries($payroll);
        } else {
            $payslips = collect();
            $totalGross = 0;
            $totalEpfQualifying = 0;
            $totalEpfEmployee = 0;
            $totalEpfEmployer = 0;
            $totalEtfEmployer = 0;
            $totalApit = 0;
            $totalNetPay = 0;
            $totalDirectSalaries = 0;
            $totalIndirectSalaries = 0;

            foreach ($employees as $emp) {
                $calc = $this->calcService->calculateEmployeePayroll($emp, $month, $year);
                $totalGross += $calc['gross_salary'];
                $totalEpfQualifying += $calc['total_for_epf'];
                $totalEpfEmployee += $calc['epf_employee'];
                $totalEpfEmployer += $calc['epf_employer'];
                $totalEtfEmployer += $calc['etf_employer'];
                $totalApit += $calc['apit_tax'];
                $totalNetPay += $calc['net_salary'];

                if ($calc['cost_classification'] === 'Direct') {
                    $totalDirectSalaries += $calc['gross_salary'];
                } else {
                    $totalIndirectSalaries += $calc['gross_salary'];
                }
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
            'totalEpfQualifying',
            'totalEpfEmployee',
            'totalEpfEmployer',
            'totalEtfEmployer',
            'totalApit',
            'totalNetPay',
            'totalDirectSalaries',
            'totalIndirectSalaries',
            'journalData',
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

            $employees = Employee::with(['user', 'department', 'designation'])->get();

            $totBasic = 0;
            $totAllowances = 0;
            $totGross = 0;
            $totEpfQualifying = 0;
            $totEpfEmp = 0;
            $totEpfEmployer = 0;
            $totEtfEmployer = 0;
            $totApit = 0;
            $totNet = 0;
            $totDirectSalaries = 0;
            $totIndirectSalaries = 0;
            $totDirectEpfEmployer = 0;
            $totIndirectEpfEmployer = 0;
            $totDirectEtfEmployer = 0;
            $totIndirectEtfEmployer = 0;
            $totExpenseRecovery = 0;
            $totLoanDeductions = 0;
            $totAdvanceDeductions = 0;

            foreach ($employees as $emp) {
                // Collect any form overrides for dynamic lines
                $inputs = [
                    'basic_salary' => $request->input("basic_salary.{$emp->id}"),
                    'increments_basic' => $request->input("increments_basic.{$emp->id}"),
                    'budget_allowance' => $request->input("budget_allowance.{$emp->id}"),
                    'travelling_allowance' => $request->input("travelling_allowance.{$emp->id}"),
                    'cost_of_living_allowance' => $request->input("cost_of_living_allowance.{$emp->id}"),
                    'increments_allowance' => $request->input("increments_allowance.{$emp->id}"),
                    'fixed_allowance' => $request->input("fixed_allowance.{$emp->id}"),
                    'ot_hours' => $request->input("ot_hours.{$emp->id}", 0),
                    'ot_amount' => $request->input("ot_amount.{$emp->id}"),
                    'shift_allowance' => $request->input("shift_allowance.{$emp->id}"),
                    'incentive_commission' => $request->input("incentive_commission.{$emp->id}", $request->input("performance_incentive.{$emp->id}", 0)),
                    'salary_arrears_basic' => $request->input("salary_arrears_basic.{$emp->id}", 0),
                    'salary_arrears_allowance' => $request->input("salary_arrears_allowance.{$emp->id}", 0),
                    'no_pay_days' => $request->input("no_pay_days.{$emp->id}", 0),
                    'personal_expense_recovery' => $request->input("personal_expense_recovery.{$emp->id}", 0),
                    'other_deductions' => $request->input("other_deductions.{$emp->id}", 0),
                ];

                // Run Statutory & Payroll Calculation Engine
                $calc = $this->calcService->calculateEmployeePayroll($emp, $month, $year, array_filter($inputs, fn($v) => !is_null($v)));

                // Update or Create Payslip Record
                $psRecord = Payslip::updateOrCreate(
                    ['payroll_id' => $payroll->id, 'employee_id' => $emp->id],
                    [
                        'month' => $month,
                        'year' => $year,
                        'staff_category' => $calc['staff_category'],
                        'payment_method' => $calc['payment_method'],
                        'department_name' => $calc['department_name'],
                        'cost_classification' => $calc['cost_classification'],

                        // Base Pay Architecture
                        'basic_salary' => $calc['basic_salary'],
                        'increments_basic' => $calc['increments_basic'],
                        'budget_allowance' => $calc['budget_allowance'],
                        'total_base_pay' => $calc['total_base_pay'],

                        // Fixed Allowances Architecture
                        'travelling_allowance' => $calc['travelling_allowance'],
                        'cost_of_living_allowance' => $calc['cost_of_living_allowance'],
                        'increments_allowance' => $calc['increments_allowance'],
                        'fixed_allowance' => $calc['fixed_allowance'],
                        'total_fixed_allowance' => $calc['total_fixed_allowance'],

                        // Variable Pay Architecture
                        'ot_hours' => $calc['ot_hours'],
                        'ot_amount' => $calc['ot_amount'],
                        'shift_allowance' => $calc['shift_allowance'],
                        'performance_incentive' => $calc['incentive_commission'],
                        'incentive_commission' => $calc['incentive_commission'],
                        'salary_arrears_basic' => $calc['salary_arrears_basic'],
                        'salary_arrears_allowance' => $calc['salary_arrears_allowance'],
                        'total_variable_pay' => $calc['total_variable_pay'],

                        // Attendance Adjustments
                        'no_pay_days' => $calc['no_pay_days'],
                        'no_pay_basic_deduction' => $calc['no_pay_basic_deduction'],
                        'no_pay_allowance_deduction' => $calc['no_pay_allowance_deduction'],
                        'total_no_pay_deduction' => $calc['total_no_pay_deduction'],
                        'no_pay_deduction' => $calc['total_no_pay_deduction'],

                        // Statutory Calculations
                        'gross_salary' => $calc['gross_salary'],
                        'total_for_epf' => $calc['total_for_epf'],
                        'epf_employee' => $calc['epf_employee'],
                        'epf_employer' => $calc['epf_employer'],
                        'etf_employer' => $calc['etf_employer'],
                        'apit_tax' => $calc['apit_tax'],

                        // Recoveries & Deductions
                        'loan_installment' => $calc['loan_installment'],
                        'salary_advance' => $calc['salary_advance'],
                        'personal_expense_recovery' => $calc['personal_expense_recovery'],
                        'other_deductions' => $calc['other_deductions'],
                        'total_deductions' => $calc['total_deductions'],

                        'net_salary' => $calc['net_salary'],
                        'status' => 'Processed',
                    ]
                );

                // Loan Auto-Repayment Ledger Entry
                $activeLoan = $calc['active_loan'];
                $loanInstallment = $calc['loan_installment'];
                if ($activeLoan && $loanInstallment > 0) {
                    $newRemaining = max(0, $activeLoan->remaining_balance - $loanInstallment);
                    $newTotalPaid = $activeLoan->total_paid + $loanInstallment;
                    $loanStatus = $newRemaining <= 0 ? 'Completed' : 'Active';

                    LoanRepayment::create([
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

                // Salary Advance Status Update
                $advance = $calc['advance_record'];
                if ($advance && $calc['salary_advance'] > 0) {
                    $advance->update(['status' => 'Deducted']);
                }

                // Accumulate Aggregates
                $totBasic += $calc['total_base_pay'];
                $totAllowances += ($calc['total_fixed_allowance'] + $calc['total_variable_pay']);
                $totGross += $calc['gross_salary'];
                $totEpfQualifying += $calc['total_for_epf'];
                $totEpfEmp += $calc['epf_employee'];
                $totEpfEmployer += $calc['epf_employer'];
                $totEtfEmployer += $calc['etf_employer'];
                $totApit += $calc['apit_tax'];
                $totNet += $calc['net_salary'];
                $totExpenseRecovery += $calc['personal_expense_recovery'];
                $totLoanDeductions += $calc['loan_installment'];
                $totAdvanceDeductions += $calc['salary_advance'];

                if ($calc['cost_classification'] === 'Direct') {
                    $totDirectSalaries += $calc['gross_salary'];
                    $totDirectEpfEmployer += $calc['epf_employer'];
                    $totDirectEtfEmployer += $calc['etf_employer'];
                } else {
                    $totIndirectSalaries += $calc['gross_salary'];
                    $totIndirectEpfEmployer += $calc['epf_employer'];
                    $totIndirectEtfEmployer += $calc['etf_employer'];
                }
            }

            $payroll->update([
                'cycle_name' => $cycleName,
                'status' => 'Processed',
                'total_basic' => $totBasic,
                'total_allowances' => $totAllowances,
                'total_gross' => $totGross,
                'total_epf_qualifying' => $totEpfQualifying,
                'total_epf_employee' => $totEpfEmp,
                'total_epf_employer' => $totEpfEmployer,
                'total_etf_employer' => $totEtfEmployer,
                'total_apit_tax' => $totApit,
                'total_net_pay' => $totNet,
                'total_direct_salaries' => $totDirectSalaries,
                'total_indirect_salaries' => $totIndirectSalaries,
                'total_direct_epf_employer' => $totDirectEpfEmployer,
                'total_indirect_epf_employer' => $totIndirectEpfEmployer,
                'total_direct_etf_employer' => $totDirectEtfEmployer,
                'total_indirect_etf_employer' => $totIndirectEtfEmployer,
                'total_expense_recovery' => $totExpenseRecovery,
                'total_loan_deductions' => $totLoanDeductions,
                'total_advance_deductions' => $totAdvanceDeductions,
                'processed_at' => now(),
            ]);
        });

        return redirect()->route('payroll.index', ['month' => $month, 'year' => $year])
            ->with('success', "Payroll for {$monthName} {$year} processed successfully with Sri Lanka statutory formulas, dynamic No-Pay, direct/indirect accounting & loan deductions!");
    }

    public function exportMasterRegister($id)
    {
        $this->authorizeAdminOrHr();

        $payroll = Payroll::with(['payslips.employee.user', 'payslips.employee.department', 'payslips.employee.designation'])->findOrFail($id);
        $filename = "Salary_Master_Register_{$payroll->month}_{$payroll->year}.csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($payroll) {
            $file = fopen('php://output', 'w');

            // Header columns
            fputcsv($file, [
                'Emp ID', 'EPF No', 'Employee Name', 'Department', 'Designation', 'Cost Center', 'Staff Type',
                'Basic Salary', 'Increments (Basic)', 'Budget Allowance', 'Total Base Pay',
                'Travelling Allw', 'COLA Allw', 'Increments (Allw)', 'Other Fixed Allw', 'Total Fixed Allw',
                'OT Hours', 'OT Pay', 'Shift Allw', 'Incentive / Commission', 'Arrears (Basic)', 'Arrears (Allw)', 'Total Variable Pay',
                'No-Pay Days', 'No-Pay (Basic)', 'No-Pay (Allw)', 'Total No-Pay Deducted',
                'Total For EPF', 'Gross Salary',
                'EPF Employee (8%)', 'EPF Employer (12%)', 'Total EPF (20%)', 'ETF Employer (3%)', 'APIT / PAYE Tax',
                'Salary Advance', 'Loan Repayment', 'PickMe / Expense Recovery', 'Other Deductions', 'Total Deductions',
                'Net Salary (LKR)', 'Payment Method'
            ]);

            foreach ($payroll->payslips as $ps) {
                $emp = $ps->employee;
                fputcsv($file, [
                    $emp->employee_id_number ?? "EMP-{$emp->id}",
                    $emp->epf_registration_no ?? 'N/A',
                    $emp->user->name ?? 'Employee',
                    $ps->department_name ?: ($emp->department->name ?? 'Corporate'),
                    $emp->designation->name ?? 'Staff',
                    $ps->cost_classification ?? 'Direct',
                    $ps->staff_category ?? 'Executive',

                    number_format($ps->basic_salary, 2, '.', ''),
                    number_format($ps->increments_basic, 2, '.', ''),
                    number_format($ps->budget_allowance, 2, '.', ''),
                    number_format($ps->total_base_pay ?: ($ps->basic_salary + $ps->increments_basic + $ps->budget_allowance), 2, '.', ''),

                    number_format($ps->travelling_allowance, 2, '.', ''),
                    number_format($ps->cost_of_living_allowance, 2, '.', ''),
                    number_format($ps->increments_allowance, 2, '.', ''),
                    number_format($ps->fixed_allowance, 2, '.', ''),
                    number_format($ps->total_fixed_allowance ?: ($ps->travelling_allowance + $ps->cost_of_living_allowance + $ps->increments_allowance + $ps->fixed_allowance), 2, '.', ''),

                    $ps->ot_hours,
                    number_format($ps->ot_amount, 2, '.', ''),
                    number_format($ps->shift_allowance, 2, '.', ''),
                    number_format($ps->incentive_commission ?: $ps->performance_incentive, 2, '.', ''),
                    number_format($ps->salary_arrears_basic, 2, '.', ''),
                    number_format($ps->salary_arrears_allowance, 2, '.', ''),
                    number_format($ps->total_variable_pay ?: ($ps->ot_amount + $ps->shift_allowance + $ps->incentive_commission + $ps->salary_arrears_basic + $ps->salary_arrears_allowance), 2, '.', ''),

                    $ps->no_pay_days,
                    number_format($ps->no_pay_basic_deduction, 2, '.', ''),
                    number_format($ps->no_pay_allowance_deduction, 2, '.', ''),
                    number_format($ps->total_no_pay_deduction ?: $ps->no_pay_deduction, 2, '.', ''),

                    number_format($ps->total_for_epf, 2, '.', ''),
                    number_format($ps->gross_salary, 2, '.', ''),

                    number_format($ps->epf_employee, 2, '.', ''),
                    number_format($ps->epf_employer, 2, '.', ''),
                    number_format($ps->epf_employee + $ps->epf_employer, 2, '.', ''),
                    number_format($ps->etf_employer, 2, '.', ''),
                    number_format($ps->apit_tax, 2, '.', ''),

                    number_format($ps->salary_advance, 2, '.', ''),
                    number_format($ps->loan_installment, 2, '.', ''),
                    number_format($ps->personal_expense_recovery, 2, '.', ''),
                    number_format($ps->other_deductions, 2, '.', ''),
                    number_format($ps->total_deductions, 2, '.', ''),

                    number_format($ps->net_salary, 2, '.', ''),
                    $ps->payment_method ?? 'Bank Transfer'
                ]);
            }

            // Totals row
            fputcsv($file, []);
            fputcsv($file, [
                'TOTALS', '', '', '', '', '', '',
                number_format($payroll->payslips->sum('basic_salary'), 2, '.', ''),
                number_format($payroll->payslips->sum('increments_basic'), 2, '.', ''),
                number_format($payroll->payslips->sum('budget_allowance'), 2, '.', ''),
                number_format($payroll->payslips->sum('total_base_pay'), 2, '.', ''),
                number_format($payroll->payslips->sum('travelling_allowance'), 2, '.', ''),
                number_format($payroll->payslips->sum('cost_of_living_allowance'), 2, '.', ''),
                number_format($payroll->payslips->sum('increments_allowance'), 2, '.', ''),
                number_format($payroll->payslips->sum('fixed_allowance'), 2, '.', ''),
                number_format($payroll->payslips->sum('total_fixed_allowance'), 2, '.', ''),
                $payroll->payslips->sum('ot_hours'),
                number_format($payroll->payslips->sum('ot_amount'), 2, '.', ''),
                number_format($payroll->payslips->sum('shift_allowance'), 2, '.', ''),
                number_format($payroll->payslips->sum('incentive_commission'), 2, '.', ''),
                number_format($payroll->payslips->sum('salary_arrears_basic'), 2, '.', ''),
                number_format($payroll->payslips->sum('salary_arrears_allowance'), 2, '.', ''),
                number_format($payroll->payslips->sum('total_variable_pay'), 2, '.', ''),
                $payroll->payslips->sum('no_pay_days'),
                number_format($payroll->payslips->sum('no_pay_basic_deduction'), 2, '.', ''),
                number_format($payroll->payslips->sum('no_pay_allowance_deduction'), 2, '.', ''),
                number_format($payroll->payslips->sum('total_no_pay_deduction'), 2, '.', ''),
                number_format($payroll->total_epf_qualifying ?: $payroll->payslips->sum('total_for_epf'), 2, '.', ''),
                number_format($payroll->total_gross, 2, '.', ''),
                number_format($payroll->total_epf_employee, 2, '.', ''),
                number_format($payroll->total_epf_employer, 2, '.', ''),
                number_format($payroll->total_epf_employee + $payroll->total_epf_employer, 2, '.', ''),
                number_format($payroll->total_etf_employer, 2, '.', ''),
                number_format($payroll->total_apit_tax, 2, '.', ''),
                number_format($payroll->total_advance_deductions ?: $payroll->payslips->sum('salary_advance'), 2, '.', ''),
                number_format($payroll->total_loan_deductions ?: $payroll->payslips->sum('loan_installment'), 2, '.', ''),
                number_format($payroll->total_expense_recovery ?: $payroll->payslips->sum('personal_expense_recovery'), 2, '.', ''),
                number_format($payroll->payslips->sum('other_deductions'), 2, '.', ''),
                number_format($payroll->payslips->sum('total_deductions'), 2, '.', ''),
                number_format($payroll->total_net_pay, 2, '.', ''),
                ''
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportEpfCForm($id)
    {
        $this->authorizeAdminOrHr();

        $payroll = Payroll::with(['payslips.employee.user'])->findOrFail($id);
        $filename = "EPF_C_Form_Remittance_{$payroll->month}_{$payroll->year}.csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($payroll) {
            $file = fopen('php://output', 'w');

            // C-Form Statutory Header
            fputcsv($file, ['EMPLOYEES PROVIDENT FUND (EPF) - MONTHLY REMITTANCE STATEMENT (FORM C)']);
            fputcsv($file, ['Employer Registration No: E-88941', "Contribution Month/Year: {$payroll->month}/{$payroll->year}"]);
            fputcsv($file, []);
            fputcsv($file, ['Member EPF No', 'National ID (NIC)', 'Full Name of Member', 'Total Earnings For EPF (LKR)', 'Employee 8% Contribution (LKR)', 'Employer 12% Contribution (LKR)', 'Total 20% Contribution (LKR)']);

            $totQualifying = 0;
            $totEmp = 0;
            $totEmployer = 0;
            $totSum = 0;

            foreach ($payroll->payslips as $ps) {
                $emp = $ps->employee;
                $qualifying = $ps->total_for_epf ?: ($ps->basic_salary + $ps->increments_basic + $ps->budget_allowance);
                $epf8 = $ps->epf_employee;
                $epf12 = $ps->epf_employer;
                $total20 = round($epf8 + $epf12, 2);

                $totQualifying += $qualifying;
                $totEmp += $epf8;
                $totEmployer += $epf12;
                $totSum += $total20;

                fputcsv($file, [
                    $emp->epf_registration_no ?? 'EPF-'.$emp->id,
                    $emp->national_id ?? $emp->nic ?? 'N/A',
                    $emp->user->name ?? 'Employee',
                    number_format($qualifying, 2, '.', ''),
                    number_format($epf8, 2, '.', ''),
                    number_format($epf12, 2, '.', ''),
                    number_format($total20, 2, '.', ''),
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['TOTAL REMITTANCE', '', '', number_format($totQualifying, 2, '.', ''), number_format($totEmp, 2, '.', ''), number_format($totEmployer, 2, '.', ''), number_format($totSum, 2, '.', '')]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportJournalEntry($id)
    {
        $this->authorizeAdminOrHr();

        $payroll = Payroll::with(['payslips.employee.department'])->findOrFail($id);
        $journal = $this->accountingService->generateJournalEntries($payroll);
        $filename = "Payroll_Journal_Entry_{$payroll->month}_{$payroll->year}.csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($payroll, $journal) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ["DOUBLE-ENTRY PAYROLL JOURNAL VOUCHER - {$payroll->cycle_name}"]);
            fputcsv($file, ['Journal Ref:', "JV-PR-{$payroll->year}-" . str_pad($payroll->month, 2, '0', STR_PAD_LEFT), 'Status:', $journal['is_balanced'] ? 'BALANCED' : 'UNBALANCED']);
            fputcsv($file, []);
            fputcsv($file, ['Account Code', 'Account Name & Description', 'Classification', 'Debit (LKR)', 'Credit (LKR)', 'Ledger Remarks']);

            foreach ($journal['all_entries'] as $entry) {
                fputcsv($file, [
                    $entry['account_code'],
                    $entry['account_name'],
                    $entry['category'],
                    $entry['debit'] > 0 ? number_format($entry['debit'], 2, '.', '') : '',
                    $entry['credit'] > 0 ? number_format($entry['credit'], 2, '.', '') : '',
                    $entry['notes']
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, [
                'TOTAL', 'GRAND TOTALS & VERIFICATION', '',
                number_format($journal['total_debits'], 2, '.', ''),
                number_format($journal['total_credits'], 2, '.', ''),
                "Variance: " . number_format($journal['variance'], 2, '.', '') . " LKR (" . ($journal['is_balanced'] ? 'Balanced (Zero Difference)' : 'Warning: Difference Detected') . ")"
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getPayslipDetails($id)
    {
        $payslip = Payslip::with(['employee.user', 'employee.department', 'employee.designation', 'payroll'])->findOrFail($id);

        $role = $this->getActiveRole();
        $user = auth()->user();
        if ($role === 'Employee' && ($payslip->employee->user_id ?? null) !== $user->id) {
            abort(403, 'Unauthorized access to payslip');
        }

        return response()->json([
            'payslip' => $payslip,
            'employee' => $payslip->employee,
            'user' => $payslip->employee->user,
            'department' => $payslip->employee->department->name ?? 'Corporate',
            'designation' => $payslip->employee->designation->name ?? 'Staff',
            'cycle_name' => $payslip->payroll->cycle_name ?? "{$payslip->month}/{$payslip->year} Payroll",
        ]);
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
        $employee = $user ? Employee::with(['user', 'department', 'designation'])->where('user_id', $user->id)->first() : null;

        $yearFilter = $request->get('year', 'All');

        $query = $employee ? Payslip::with(['payroll', 'employee.user', 'employee.department', 'employee.designation'])
            ->where('employee_id', $employee->id) : Payslip::whereRaw('1=0');

        if ($yearFilter !== 'All' && is_numeric($yearFilter)) {
            $query->where('year', (int) $yearFilter);
        }

        $payslips = $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $allEmployeePayslips = $employee ? Payslip::where('employee_id', $employee->id)->get() : collect();
        $availableYears = $allEmployeePayslips->pluck('year')->unique()->sortDesc()->values();

        $totalEarned = (float) $allEmployeePayslips->sum('net_salary');
        $totalEpfEmployee = (float) $allEmployeePayslips->sum('epf_employee');
        $latestPayslip = $allEmployeePayslips->sortByDesc(fn($p) => ($p->year * 100) + $p->month)->first();

        return view('payroll::my_payslips', compact(
            'employee',
            'payslips',
            'availableYears',
            'yearFilter',
            'totalEarned',
            'totalEpfEmployee',
            'latestPayslip'
        ));
    }
}
