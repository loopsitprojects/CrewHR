<?php

namespace Modules\Payroll\Services;

use Modules\Employee\Models\Employee;
use Modules\Payroll\Models\EmployeeLoan;
use Modules\Payroll\Models\SalaryAdvance;
use Modules\Payroll\Models\OvertimeRecord;

use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    /**
     * Calculate comprehensive payroll breakdown for a single employee in a given cycle.
     */
    public function calculateEmployeePayroll(Employee $emp, int $month, int $year, array $inputs = []): array
    {
        // 1. Base Pay
        $basic = (float) ($inputs['basic_salary'] ?? ($emp->basic_salary ?: 150000));
        $incrementsBasic = (float) ($inputs['increments_basic'] ?? ($emp->increments_basic ?: 0));
        $budgetAllowance = (float) ($inputs['budget_allowance'] ?? ($emp->budget_allowance ?: 0));
        $totalBasePay = $basic + $incrementsBasic + $budgetAllowance;

        // 2. Fixed Allowances (Dynamic Employee Allowances or Legacy fields)
        $dynamicAllowances = $emp->relationLoaded('employeeAllowances') 
            ? $emp->employeeAllowances 
            : $emp->employeeAllowances()->with('allowanceType')->get();

        $epfLiableAllowanceAmount = 0;
        $travelling = (float) ($inputs['travelling_allowance'] ?? ($emp->travelling_allowance ?: 0));
        $cola = (float) ($inputs['cost_of_living_allowance'] ?? ($emp->cost_of_living_allowance ?: 0));
        $incrementsAllowance = (float) ($inputs['increments_allowance'] ?? ($emp->increments_allowance ?: 0));
        $fixedOther = (float) ($inputs['fixed_allowance'] ?? ($emp->fixed_allowance ?: 0));

        if ($dynamicAllowances->isNotEmpty()) {
            $totalFixedAllowance = 0;
            foreach ($dynamicAllowances as $ea) {
                $amt = (float) $ea->amount;
                $totalFixedAllowance += $amt;
                if ($ea->allowanceType && $ea->allowanceType->is_epf_liable) {
                    $epfLiableAllowanceAmount += $amt;
                }
            }
        } else {
            $totalFixedAllowance = $travelling + $cola + $incrementsAllowance + $fixedOther;
        }

        // 3. Variable Pay (OT, Shift, Incentives, Arrears)
        $approvedOt = OvertimeRecord::where('employee_id', $emp->id)
            ->whereYear('ot_date', $year)
            ->whereMonth('ot_date', $month)
            ->where('status', 'Approved')
            ->get();

        if ($approvedOt->isNotEmpty()) {
            $otHours = (float) $approvedOt->sum('hours');
            $otAmount = (float) $approvedOt->sum('estimated_amount');
        } else {
            $otHours = (float) ($inputs['ot_hours'] ?? 0);
            $otAmount = (float) ($inputs['ot_amount'] ?? round(($basic / 200) * $otHours * 1.5, 2));
        }

        $isShiftStaff = str_contains(strtolower($emp->staff_category ?? ''), 'shift');
        $shiftAllowance = (float) ($inputs['shift_allowance'] ?? ($isShiftStaff ? 15000 : 0));
        $incentiveCommission = (float) ($inputs['incentive_commission'] ?? ($inputs['performance_incentive'] ?? 0));
        $salaryArrearsBasic = (float) ($inputs['salary_arrears_basic'] ?? 0);
        $salaryArrearsAllowance = (float) ($inputs['salary_arrears_allowance'] ?? 0);
        $totalVariablePay = $otAmount + $shiftAllowance + $incentiveCommission + $salaryArrearsBasic + $salaryArrearsAllowance;

        // 4. Attendance & Dynamic No-Pay Deductions
        $noPayDays = (float) ($inputs['no_pay_days'] ?? 0);
        $noPaySplit = $this->calculateNoPaySplit($totalBasePay, $totalFixedAllowance, $noPayDays);
        $noPayBasic = $noPaySplit['no_pay_basic'];
        $noPayAllowance = $noPaySplit['no_pay_allowance'];
        $totalNoPay = $noPaySplit['total_no_pay'];

        // 5. Total for EPF (Qualifying Earnings for Sri Lanka EPF/ETF)
        // Formula: Base Pay + EPF Liable Allowances + Basic Arrears - No Pay Basic
        $totalForEpf = $this->calculateTotalForEpf($totalBasePay + $epfLiableAllowanceAmount, $salaryArrearsBasic, $noPayBasic);

        // 6. Statutory Deductions & Contributions (Configured from Settings)
        $statutory = $this->calculateEpfEtf(
            $totalForEpf, 
            isset($inputs['epf_employee_rate']) ? (float)$inputs['epf_employee_rate'] : null,
            isset($inputs['epf_employer_rate']) ? (float)$inputs['epf_employer_rate'] : null,
            isset($inputs['etf_employer_rate']) ? (float)$inputs['etf_employer_rate'] : null
        );
        $epfEmployee = $statutory['epf_employee'];
        $epfEmployer = $statutory['epf_employer'];
        $etfEmployer = $statutory['etf_employer'];

        // 7. Gross Salary (Total Earnings minus attendance deductions)
        $grossSalary = max(0, ($totalBasePay + $totalFixedAllowance + $totalVariablePay) - $totalNoPay);

        // 8. APIT / PAYE Tax
        if (isset($inputs['apit_tax'])) {
            $apitTax = (float) $inputs['apit_tax'];
        } elseif ($emp->apit_tax > 0) {
            $apitTax = (float) $emp->apit_tax;
        } else {
            $apitTax = $this->calculateApitTax($grossSalary);
        }

        // 9. Active Loan Auto-Deduction
        $activeLoan = EmployeeLoan::where('employee_id', $emp->id)->where('status', 'Active')->first();
        $loanInstallment = 0;
        if ($activeLoan && $activeLoan->remaining_balance > 0) {
            $loanInstallment = min((float) $activeLoan->monthly_installment, (float) $activeLoan->remaining_balance);
        }
        if (isset($inputs['loan_installment'])) {
            $loanInstallment = (float) $inputs['loan_installment'];
        }

        // 10. Salary Advance Auto-Deduction (only deduct HR-Approved advances, not Pending)
        $advance = SalaryAdvance::where('employee_id', $emp->id)
            ->where('status', 'Approved')
            ->where('year', $year)
            ->where('month', $month)
            ->first();
        $salaryAdvanceDeduction = $advance ? (float) $advance->amount : 0;
        if (isset($inputs['salary_advance'])) {
            $salaryAdvanceDeduction = (float) $inputs['salary_advance'];
        }

        // 11. Personal / Corporate Expense Recovery (e.g. PickMe personal trips, assets)
        $personalExpenseRecovery = (float) ($inputs['personal_expense_recovery'] ?? 0);
        $otherDeductions = (float) ($inputs['other_deductions'] ?? 0);

        // 12. Total Deductions and Net Salary
        $nonStatutoryDeductions = $loanInstallment + $salaryAdvanceDeduction + $personalExpenseRecovery + $otherDeductions;
        $totalDeductions = $epfEmployee + $apitTax + $nonStatutoryDeductions;
        $netSalary = max(0, $grossSalary - $totalDeductions);

        // 13. Cost Classification & Department
        $deptName = $emp->department->name ?? 'Corporate';
        $costClassification = $this->resolveCostClassification($emp, $deptName);

        return [
            'employee_id' => $emp->id,
            'month' => $month,
            'year' => $year,
            'staff_category' => $emp->staff_category ?? 'Executive',
            'payment_method' => $emp->payment_method ?? 'Bank Transfer',
            'department_name' => $deptName,
            'cost_classification' => $costClassification,

            // Base Pay
            'basic_salary' => $basic,
            'increments_basic' => $incrementsBasic,
            'budget_allowance' => $budgetAllowance,
            'total_base_pay' => $totalBasePay,

            // Fixed Allowances
            'travelling_allowance' => $travelling,
            'cost_of_living_allowance' => $cola,
            'increments_allowance' => $incrementsAllowance,
            'fixed_allowance' => $fixedOther,
            'total_fixed_allowance' => $totalFixedAllowance,

            // Variable Pay
            'ot_hours' => $otHours,
            'ot_amount' => $otAmount,
            'shift_allowance' => $shiftAllowance,
            'performance_incentive' => $incentiveCommission,
            'incentive_commission' => $incentiveCommission,
            'salary_arrears_basic' => $salaryArrearsBasic,
            'salary_arrears_allowance' => $salaryArrearsAllowance,
            'total_variable_pay' => $totalVariablePay,

            // Attendance Adjustments
            'no_pay_days' => $noPayDays,
            'no_pay_basic_deduction' => $noPayBasic,
            'no_pay_allowance_deduction' => $noPayAllowance,
            'total_no_pay_deduction' => $totalNoPay,
            'no_pay_deduction' => $totalNoPay,

            // Statutory Fields
            'total_for_epf' => $totalForEpf,
            'epf_employee' => $epfEmployee,
            'epf_employer' => $epfEmployer,
            'etf_employer' => $etfEmployer,
            'gross_salary' => $grossSalary,
            'apit_tax' => $apitTax,

            // Recoveries & Deductions
            'loan_installment' => $loanInstallment,
            'salary_advance' => $salaryAdvanceDeduction,
            'personal_expense_recovery' => $personalExpenseRecovery,
            'other_deductions' => $otherDeductions,
            'total_deductions' => $totalDeductions,

            // Net
            'net_salary' => $netSalary,

            // References
            'active_loan' => $activeLoan,
            'advance_record' => $advance,
        ];
    }

    /**
     * Compute Total for EPF based on Sri Lanka labor law rules.
     */
    public function calculateTotalForEpf(float $basePay, float $salaryArrearsBasic, float $noPayBasic): float
    {
        return max(0, round($basePay + $salaryArrearsBasic - $noPayBasic, 2));
    }

    /**
     * Compute EPF and ETF using system settings rates with defaults (EPF 8%, EPF 12%, ETF 3%).
     */
    public function calculateEpfEtf(float $totalForEpf, ?float $employeeRatePercent = null, ?float $employerRatePercent = null, ?float $etfRatePercent = null): array
    {
        $empRate = !is_null($employeeRatePercent) ? $employeeRatePercent : (float) (DB::table('settings')->where('key', 'epf_employee_rate')->value('value') ?? 8.0);
        $empLyrRate = !is_null($employerRatePercent) ? $employerRatePercent : (float) (DB::table('settings')->where('key', 'epf_employer_rate')->value('value') ?? 12.0);
        $etfRate = !is_null($etfRatePercent) ? $etfRatePercent : (float) (DB::table('settings')->where('key', 'etf_employer_rate')->value('value') ?? 3.0);

        $epfEmployee = round($totalForEpf * ($empRate / 100), 2);
        $epfEmployer = round($totalForEpf * ($empLyrRate / 100), 2);
        $etfEmployer = round($totalForEpf * ($etfRate / 100), 2);

        return [
            'epf_employee' => $epfEmployee,
            'epf_employer' => $epfEmployer,
            'etf_employer' => $etfEmployer,
            'total_epf' => round($epfEmployee + $epfEmployer, 2),
            'epf_employee_rate' => $empRate,
            'epf_employer_rate' => $empLyrRate,
            'etf_employer_rate' => $etfRate,
        ];
    }

    /**
     * Calculate dynamic No Pay deduction split between Basic and Fixed Allowances.
     */
    public function calculateNoPaySplit(float $totalBasePay, float $totalFixedAllowance, float $noPayDays, int $workingDays = 30): array
    {
        if ($noPayDays <= 0 || $workingDays <= 0) {
            return [
                'no_pay_basic' => 0.0,
                'no_pay_allowance' => 0.0,
                'total_no_pay' => 0.0,
            ];
        }

        $dailyBase = $totalBasePay / $workingDays;
        $dailyAllowance = $totalFixedAllowance / $workingDays;

        $noPayBasic = min($totalBasePay, round($dailyBase * $noPayDays, 2));
        $noPayAllowance = min($totalFixedAllowance, round($dailyAllowance * $noPayDays, 2));
        $totalNoPay = round($noPayBasic + $noPayAllowance, 2);

        return [
            'no_pay_basic' => $noPayBasic,
            'no_pay_allowance' => $noPayAllowance,
            'total_no_pay' => $totalNoPay,
        ];
    }

    /**
     * Calculate Sri Lanka Inland Revenue Department APIT monthly progressive tax brackets.
     */
    public function calculateApitTax(float $taxableIncome, ?float $customThreshold = null): float
    {
        $threshold = !is_null($customThreshold) ? $customThreshold : (float) (DB::table('settings')->where('key', 'apit_tax_threshold')->value('value') ?? 100000.0);

        if ($taxableIncome <= $threshold) {
            return 0.0;
        }

        $taxableRem = $taxableIncome - $threshold;
        $tax = 0.0;
        $tierSize = 41666.67; // 500,000 / 12 months

        // Tier 1: 6% on next 41,666.67
        $t1 = min($taxableRem, $tierSize);
        $tax += $t1 * 0.06;
        $taxableRem -= $t1;
        if ($taxableRem <= 0) return round($tax, 2);

        // Tier 2: 12% on next 41,666.67
        $t2 = min($taxableRem, $tierSize);
        $tax += $t2 * 0.12;
        $taxableRem -= $t2;
        if ($taxableRem <= 0) return round($tax, 2);

        // Tier 3: 18% on next 41,666.67
        $t3 = min($taxableRem, $tierSize);
        $tax += $t3 * 0.18;
        $taxableRem -= $t3;
        if ($taxableRem <= 0) return round($tax, 2);

        // Tier 4: 24% on next 41,666.67
        $t4 = min($taxableRem, $tierSize);
        $tax += $t4 * 0.24;
        $taxableRem -= $t4;
        if ($taxableRem <= 0) return round($tax, 2);

        // Tier 5: 30% on next 41,666.67
        $t5 = min($taxableRem, $tierSize);
        $tax += $t5 * 0.30;
        $taxableRem -= $t5;
        if ($taxableRem <= 0) return round($tax, 2);

        // Tier 6: 36% on excess
        $tax += $taxableRem * 0.36;

        return round($tax, 2);
    }

    /**
     * Determine whether an employee belongs to Direct Cost (Cost of Sales) or Indirect (Administrative).
     */
    public function resolveCostClassification(Employee $emp, ?string $deptName = null): string
    {
        if (!empty($emp->cost_classification)) {
            return in_array($emp->cost_classification, ['Direct', 'Indirect']) ? $emp->cost_classification : 'Direct';
        }

        $dept = strtolower($deptName ?: ($emp->department->name ?? ''));
        
        $directKeywords = ['creative', 'video', 'dm', 'digital marketing', 'it', 'software', 'engineering', 'design', 'production', 'content'];
        foreach ($directKeywords as $kw) {
            if (str_contains($dept, $kw)) {
                return 'Direct';
            }
        }

        $indirectKeywords = ['corporate', 'hr', 'human resources', 'admin', 'administration', 'finance', 'management', 'accounts', 'legal'];
        foreach ($indirectKeywords as $kw) {
            if (str_contains($dept, $kw)) {
                return 'Indirect';
            }
        }

        return 'Direct';
    }
}
