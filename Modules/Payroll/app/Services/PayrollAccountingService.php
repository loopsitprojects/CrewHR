<?php

namespace Modules\Payroll\Services;

use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\Payslip;

class PayrollAccountingService
{
    /**
     * Generate double-entry journal entries for a processed payroll cycle.
     */
    public function generateJournalEntries(Payroll $payroll): array
    {
        $payslips = $payroll->payslips;

        // Group payslips by Cost Classification
        $directPayslips = $payslips->filter(fn($ps) => ($ps->cost_classification ?? 'Direct') === 'Direct');
        $indirectPayslips = $payslips->filter(fn($ps) => ($ps->cost_classification ?? 'Direct') === 'Indirect');

        // Direct Cost aggregates
        $directGross = (float) $directPayslips->sum('gross_salary');
        $directEpfEmployer = (float) $directPayslips->sum('epf_employer');
        $directEtfEmployer = (float) $directPayslips->sum('etf_employer');

        // Indirect Cost aggregates
        $indirectGross = (float) $indirectPayslips->sum('gross_salary');
        $indirectEpfEmployer = (float) $indirectPayslips->sum('epf_employer');
        $indirectEtfEmployer = (float) $indirectPayslips->sum('etf_employer');

        // Deductions & Liability aggregates
        $totalNetPay = (float) $payslips->sum('net_salary');
        $totalEpfEmployee = (float) $payslips->sum('epf_employee');
        $totalEpfEmployer = (float) $payslips->sum('epf_employer');
        $totalEpfPayable = round($totalEpfEmployee + $totalEpfEmployer, 2); // 20% Total (8% + 12%)
        $totalEtfPayable = (float) $payslips->sum('etf_employer'); // 3%
        $totalApitPayable = (float) $payslips->sum('apit_tax');
        $totalLoanDeductions = (float) $payslips->sum('loan_installment');
        $totalAdvanceDeductions = (float) $payslips->sum('salary_advance');
        $totalExpenseRecovery = (float) ($payslips->sum('personal_expense_recovery') + $payslips->sum('other_deductions') - $payslips->sum('total_no_pay_deduction'));
        if ($totalExpenseRecovery < 0) {
            $totalExpenseRecovery = (float) $payslips->sum('personal_expense_recovery') + (float) $payslips->sum('other_deductions');
        }

        // Prepare Double-Entry Journal Lines
        $debits = [
            [
                'account_code' => '5100',
                'account_name' => 'Direct Salaries & Wages Expense (Cost of Sales / Production)',
                'category' => 'Direct Cost',
                'debit' => $directGross,
                'credit' => 0.0,
                'notes' => "Direct production staff base salaries, allowances & incentives ({$directPayslips->count()} staff)",
            ],
            [
                'account_code' => '5110',
                'account_name' => 'Direct EPF Employer Contribution (12%)',
                'category' => 'Direct Cost',
                'debit' => $directEpfEmployer,
                'credit' => 0.0,
                'notes' => '12% statutory employer EPF on qualifying earnings for direct staff',
            ],
            [
                'account_code' => '5120',
                'account_name' => 'Direct ETF Employer Contribution (3%)',
                'category' => 'Direct Cost',
                'debit' => $directEtfEmployer,
                'credit' => 0.0,
                'notes' => '3% statutory employer ETF on qualifying earnings for direct staff',
            ],
            [
                'account_code' => '6100',
                'account_name' => 'Indirect Administrative Salaries Expense',
                'category' => 'Indirect Expense',
                'debit' => $indirectGross,
                'credit' => 0.0,
                'notes' => "Administrative, Corporate & HR staff gross earnings ({$indirectPayslips->count()} staff)",
            ],
            [
                'account_code' => '6110',
                'account_name' => 'Indirect EPF Employer Contribution (12%)',
                'category' => 'Indirect Expense',
                'debit' => $indirectEpfEmployer,
                'credit' => 0.0,
                'notes' => '12% statutory employer EPF on qualifying earnings for indirect staff',
            ],
            [
                'account_code' => '6120',
                'account_name' => 'Indirect ETF Employer Contribution (3%)',
                'category' => 'Indirect Expense',
                'debit' => $indirectEtfEmployer,
                'credit' => 0.0,
                'notes' => '3% statutory employer ETF on qualifying earnings for indirect staff',
            ],
        ];

        $credits = [
            [
                'account_code' => '2100',
                'account_name' => 'Net Salaries Payable / Bank Clearing',
                'category' => 'Current Liability',
                'debit' => 0.0,
                'credit' => $totalNetPay,
                'notes' => 'Total net salaries payable via bank transfer & cash disbursement',
            ],
            [
                'account_code' => '2110',
                'account_name' => 'EPF 20% Remittance Payable (8% Employee + 12% Employer)',
                'category' => 'Current Liability',
                'debit' => 0.0,
                'credit' => $totalEpfPayable,
                'notes' => 'Total EPF liability payable to Central Bank EPF Department C-Form',
            ],
            [
                'account_code' => '2120',
                'account_name' => 'ETF 3% Remittance Payable',
                'category' => 'Current Liability',
                'debit' => 0.0,
                'credit' => $totalEtfPayable,
                'notes' => 'Total ETF liability payable to Employee Trust Fund Board',
            ],
            [
                'account_code' => '2130',
                'account_name' => 'APIT / PAYE Withholding Tax Payable',
                'category' => 'Current Liability',
                'debit' => 0.0,
                'credit' => $totalApitPayable,
                'notes' => 'Advance Personal Income Tax deducted for remittance to IRD',
            ],
            [
                'account_code' => '1250',
                'account_name' => 'Staff Loans Receivable Clearing',
                'category' => 'Current Asset',
                'debit' => 0.0,
                'credit' => $totalLoanDeductions,
                'notes' => 'Monthly recovery applied against staff loan principal balances',
            ],
            [
                'account_code' => '1260',
                'account_name' => 'Salary Advances Clearing Control',
                'category' => 'Current Asset',
                'debit' => 0.0,
                'credit' => $totalAdvanceDeductions,
                'notes' => 'Settlement of approved salary advances issued during the cycle',
            ],
            [
                'account_code' => '1270',
                'account_name' => 'Personal & Expense Recoveries (PickMe / Claims)',
                'category' => 'Expense / Asset Offset',
                'debit' => 0.0,
                'credit' => (float) $payslips->sum('personal_expense_recovery') + (float) $payslips->sum('other_deductions'),
                'notes' => 'Recoveries for personal PickMe rides, asset adjustments, and other deductions',
            ],
        ];

        // Filter out zero-balance lines for cleaner ledger presentation
        $activeDebits = array_values(array_filter($debits, fn($l) => $l['debit'] > 0));
        $activeCredits = array_values(array_filter($credits, fn($l) => $l['credit'] > 0));

        $totalDebits = round(array_sum(array_column($debits, 'debit')), 2);
        $totalCredits = round(array_sum(array_column($credits, 'credit')), 2);
        $variance = round($totalDebits - $totalCredits, 2);
        $isBalanced = abs($variance) < 0.01;

        return [
            'payroll_id' => $payroll->id,
            'cycle_name' => $payroll->cycle_name ?? "{$payroll->month}/{$payroll->year} Payroll",
            'month' => $payroll->month,
            'year' => $payroll->year,
            'debits' => $activeDebits,
            'credits' => $activeCredits,
            'all_entries' => array_merge($activeDebits, $activeCredits),
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'variance' => $variance,
            'is_balanced' => $isBalanced,
            'summary' => [
                'direct_salaries' => $directGross,
                'direct_epf_employer' => $directEpfEmployer,
                'direct_etf_employer' => $directEtfEmployer,
                'total_direct_cost' => round($directGross + $directEpfEmployer + $directEtfEmployer, 2),
                'indirect_salaries' => $indirectGross,
                'indirect_epf_employer' => $indirectEpfEmployer,
                'indirect_etf_employer' => $indirectEtfEmployer,
                'total_indirect_cost' => round($indirectGross + $indirectEpfEmployer + $indirectEtfEmployer, 2),
                'total_employer_cost' => round($totalDebits, 2),
                'total_net_disbursement' => $totalNetPay,
            ],
        ];
    }
}
