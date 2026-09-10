<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Employee\Models\Employee;
use App\Models\User;

class PayrollModulesDummySeeder extends Seeder
{
    public function run(): void
    {
        $empSahan = Employee::where('employee_id_number', 'EMP-0104')->first() ?? Employee::first();
        $empShimal = Employee::where('employee_id_number', 'REQ-1092')->first() ?? Employee::skip(1)->first();
        $empArosh = Employee::where('employee_id_number', 'REQ-1093')->first() ?? Employee::skip(2)->first();
        $empAnjalie = Employee::where('employee_id_number', 'EMP-0100')->first() ?? Employee::skip(3)->first();
        $userSuper = User::where('username', 'admin')->first() ?? User::first();
        $userSupuni = User::where('username', 'supuni')->first() ?? $userSuper;

        if (!$empSahan) {
            return;
        }

        // 1. Loan Types
        $loanTypeDistress = \Modules\Payroll\Models\LoanType::updateOrCreate(['code' => 'DISTRESS'], [
            'name' => 'Distress / Welfare Loan',
            'description' => 'Interest-free emergency financial assistance for urgent employee medical or family distress.',
            'max_amount' => 150000,
            'interest_rate_annual' => 0.00,
            'max_repayment_months' => 24,
            'status' => 'Active',
        ]);

        $loanTypeHousing = \Modules\Payroll\Models\LoanType::updateOrCreate(['code' => 'HOUSING'], [
            'name' => 'Housing & Renovation Loan',
            'description' => 'Subsidized low-interest loan for home purchase, building or renovation.',
            'max_amount' => 500000,
            'interest_rate_annual' => 4.50,
            'max_repayment_months' => 48,
            'status' => 'Active',
        ]);

        $loanTypeFestival = \Modules\Payroll\Models\LoanType::updateOrCreate(['code' => 'FESTIVAL'], [
            'name' => 'Festival Advance / Loan',
            'description' => 'Seasonal advance given during Sinhala & Tamil New Year or Christmas season.',
            'max_amount' => 50000,
            'interest_rate_annual' => 0.00,
            'max_repayment_months' => 10,
            'status' => 'Active',
        ]);

        // 2. Dummy Loans
        $loanSahan = \Modules\Payroll\Models\EmployeeLoan::updateOrCreate(
            ['loan_number' => 'LN-2026-001'],
            [
                'employee_id' => $empSahan->id,
                'loan_type_id' => $loanTypeDistress->id,
                'loan_title' => 'Emergency Home Appliance & Welfare Support',
                'principal_amount' => 120000.00,
                'interest_rate' => 0.00,
                'repayment_months' => 12,
                'monthly_installment' => 10000.00,
                'total_paid' => 30000.00,
                'remaining_balance' => 90000.00,
                'purpose' => 'Urgent home roof repair and electrical upgrades before monsoon season.',
                'status' => 'Active',
                'start_date' => '2026-06-01',
                'applied_at' => '2026-05-25 10:00:00',
                'approved_at' => '2026-05-28 14:30:00',
                'approved_by_user_id' => $userSuper->id,
            ]
        );

        if ($empArosh) {
            $loanArosh = \Modules\Payroll\Models\EmployeeLoan::updateOrCreate(
                ['loan_number' => 'LN-2026-002'],
                [
                    'employee_id' => $empArosh->id,
                    'loan_type_id' => $loanTypeHousing->id,
                    'loan_title' => 'Housing Construction Phase 2 Loan',
                    'principal_amount' => 360000.00,
                    'interest_rate' => 4.50,
                    'repayment_months' => 24,
                    'monthly_installment' => 15000.00,
                    'total_paid' => 45000.00,
                    'remaining_balance' => 315000.00,
                    'purpose' => 'Finishing upstairs bedroom and solar panel installation.',
                    'status' => 'Active',
                    'start_date' => '2026-05-01',
                    'applied_at' => '2026-04-20 09:15:00',
                    'approved_at' => '2026-04-25 16:00:00',
                    'approved_by_user_id' => $userSuper->id,
                ]
            );
        }

        // Loan Repayment records
        \Modules\Payroll\Models\LoanRepayment::updateOrCreate(
            ['employee_loan_id' => $loanSahan->id, 'amount_paid' => 10000.00, 'paid_date' => '2026-08-28 17:00:00'],
            [
                'principal_paid' => 10000.00,
                'interest_paid' => 0.00,
                'remaining_balance_after' => 90000.00,
                'repayment_type' => 'Payroll Auto-Deduction',
                'notes' => 'August 2026 Payroll Auto Deduction installment 3/12',
            ]
        );

        // 3. Dummy Salary Advances
        \Modules\Payroll\Models\SalaryAdvance::updateOrCreate(
            ['advance_number' => 'ADV-2026-001'],
            [
                'employee_id' => $empSahan->id,
                'amount' => 25000.00,
                'month' => 9,
                'year' => 2026,
                'reason' => 'Advance needed for child school admission and term book fees.',
                'status' => 'Pending',
                'requested_date' => '2026-09-02',
            ]
        );

        if ($empShimal) {
            \Modules\Payroll\Models\SalaryAdvance::updateOrCreate(
                ['advance_number' => 'ADV-2026-002'],
                [
                    'employee_id' => $empShimal->id,
                    'amount' => 15000.00,
                    'month' => 9,
                    'year' => 2026,
                    'reason' => 'Medical checkup and prescription costs for parents.',
                    'status' => 'Approved',
                    'requested_date' => '2026-09-01',
                    'approved_at' => '2026-09-03 11:20:00',
                    'approved_by_user_id' => $userSupuni->id,
                ]
            );
        }

        if ($empAnjalie) {
            \Modules\Payroll\Models\SalaryAdvance::updateOrCreate(
                ['advance_number' => 'ADV-2026-003'],
                [
                    'employee_id' => $empAnjalie->id,
                    'amount' => 30000.00,
                    'month' => 8,
                    'year' => 2026,
                    'reason' => 'Vehicle minor repair expenses during outstation travel.',
                    'status' => 'Deducted',
                    'requested_date' => '2026-08-10',
                    'approved_at' => '2026-08-12 09:30:00',
                    'approved_by_user_id' => $userSuper->id,
                ]
            );
        }

        // 4. Dummy Overtime Records
        $sahanHourly = round(($empSahan->basic_salary ?: 190000) / 200, 2);
        $shimalHourly = $empShimal ? round(($empShimal->basic_salary ?: 150000) / 200, 2) : 750.00;

        \Modules\Payroll\Models\OvertimeRecord::updateOrCreate(
            ['ot_number' => 'OT-2026-001'],
            [
                'employee_id' => $empSahan->id,
                'ot_date' => '2026-09-05',
                'start_time' => '17:00',
                'end_time' => '20:30',
                'hours' => 3.5,
                'rate_multiplier_type' => 'Standard (1.5x)',
                'multiplier' => 1.5,
                'hourly_rate' => $sahanHourly,
                'estimated_amount' => round(3.5 * $sahanHourly * 1.5, 2),
                'reason' => 'Emergency production database migration and Kubernetes cluster patch release.',
                'status' => 'Approved',
                'approved_at' => '2026-09-06 10:00:00',
                'approved_by_user_id' => $userSupuni->id,
            ]
        );

        \Modules\Payroll\Models\OvertimeRecord::updateOrCreate(
            ['ot_number' => 'OT-2026-002'],
            [
                'employee_id' => $empSahan->id,
                'ot_date' => '2026-09-07',
                'start_time' => '17:00',
                'end_time' => '19:00',
                'hours' => 2.0,
                'rate_multiplier_type' => 'Standard (1.5x)',
                'multiplier' => 1.5,
                'hourly_rate' => $sahanHourly,
                'estimated_amount' => round(2.0 * $sahanHourly * 1.5, 2),
                'reason' => 'Post-deployment system health and monitoring metrics validation.',
                'status' => 'Pending',
            ]
        );

        if ($empShimal) {
            \Modules\Payroll\Models\OvertimeRecord::updateOrCreate(
                ['ot_number' => 'OT-2026-003'],
                [
                    'employee_id' => $empShimal->id,
                    'ot_date' => '2026-09-01',
                    'start_time' => '07:30',
                    'end_time' => '09:00',
                    'hours' => 1.5,
                    'rate_multiplier_type' => 'Standard (1.5x)',
                    'multiplier' => 1.5,
                    'hourly_rate' => $shimalHourly,
                    'estimated_amount' => round(1.5 * $shimalHourly * 1.5, 2),
                    'reason' => 'Early morning critical bugfix resolution for banking client portal.',
                    'status' => 'Approved',
                    'approved_at' => '2026-09-02 09:30:00',
                    'approved_by_user_id' => $userSupuni->id,
                ]
            );

            \Modules\Payroll\Models\OvertimeRecord::updateOrCreate(
                ['ot_number' => 'OT-2026-004'],
                [
                    'employee_id' => $empShimal->id,
                    'ot_date' => '2026-08-27',
                    'start_time' => '10:00',
                    'end_time' => '16:00',
                    'hours' => 6.0,
                    'rate_multiplier_type' => 'Holiday (2.5x)',
                    'multiplier' => 2.5,
                    'hourly_rate' => $shimalHourly,
                    'estimated_amount' => round(6.0 * $shimalHourly * 2.5, 2),
                    'reason' => 'Poya Day urgent client support and incident standby coverage.',
                    'status' => 'Approved',
                    'approved_at' => '2026-08-28 09:00:00',
                    'approved_by_user_id' => $userSuper->id,
                ]
            );
        }
    }
}
