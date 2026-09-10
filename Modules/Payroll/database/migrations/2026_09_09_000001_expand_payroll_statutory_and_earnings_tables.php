<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expand employees table
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'increments_basic')) {
                $table->decimal('increments_basic', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'budget_allowance')) {
                $table->decimal('budget_allowance', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'travelling_allowance')) {
                $table->decimal('travelling_allowance', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'cost_of_living_allowance')) {
                $table->decimal('cost_of_living_allowance', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'increments_allowance')) {
                $table->decimal('increments_allowance', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'cost_classification')) {
                $table->string('cost_classification')->nullable(); // Direct or Indirect
            }
        });

        // 2. Expand payslips table
        Schema::table('payslips', function (Blueprint $table) {
            // Base Pay Architecture
            if (!Schema::hasColumn('payslips', 'increments_basic')) {
                $table->decimal('increments_basic', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payslips', 'budget_allowance')) {
                $table->decimal('budget_allowance', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payslips', 'total_base_pay')) {
                $table->decimal('total_base_pay', 15, 2)->default(0);
            }

            // Fixed Allowances
            if (!Schema::hasColumn('payslips', 'travelling_allowance')) {
                $table->decimal('travelling_allowance', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payslips', 'cost_of_living_allowance')) {
                $table->decimal('cost_of_living_allowance', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payslips', 'increments_allowance')) {
                $table->decimal('increments_allowance', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payslips', 'total_fixed_allowance')) {
                $table->decimal('total_fixed_allowance', 15, 2)->default(0);
            }

            // Variable Pay
            if (!Schema::hasColumn('payslips', 'incentive_commission')) {
                $table->decimal('incentive_commission', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payslips', 'salary_arrears_basic')) {
                $table->decimal('salary_arrears_basic', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payslips', 'salary_arrears_allowance')) {
                $table->decimal('salary_arrears_allowance', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payslips', 'total_variable_pay')) {
                $table->decimal('total_variable_pay', 15, 2)->default(0);
            }

            // Attendance Adjustments / No-Pay Split
            if (!Schema::hasColumn('payslips', 'no_pay_basic_deduction')) {
                $table->decimal('no_pay_basic_deduction', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payslips', 'no_pay_allowance_deduction')) {
                $table->decimal('no_pay_allowance_deduction', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payslips', 'total_no_pay_deduction')) {
                $table->decimal('total_no_pay_deduction', 15, 2)->default(0);
            }

            // Statutory Calculations
            if (!Schema::hasColumn('payslips', 'total_for_epf')) {
                $table->decimal('total_for_epf', 15, 2)->default(0);
            }

            // Recoveries & Deductions
            if (!Schema::hasColumn('payslips', 'personal_expense_recovery')) {
                $table->decimal('personal_expense_recovery', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payslips', 'total_deductions')) {
                $table->decimal('total_deductions', 15, 2)->default(0);
            }

            // Cost Classification & Department Cache
            if (!Schema::hasColumn('payslips', 'department_name')) {
                $table->string('department_name')->nullable();
            }
            if (!Schema::hasColumn('payslips', 'cost_classification')) {
                $table->string('cost_classification')->default('Direct');
            }
        });

        // 3. Expand payrolls table
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'total_epf_qualifying')) {
                $table->decimal('total_epf_qualifying', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'total_direct_salaries')) {
                $table->decimal('total_direct_salaries', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'total_indirect_salaries')) {
                $table->decimal('total_indirect_salaries', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'total_direct_epf_employer')) {
                $table->decimal('total_direct_epf_employer', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'total_indirect_epf_employer')) {
                $table->decimal('total_indirect_epf_employer', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'total_direct_etf_employer')) {
                $table->decimal('total_direct_etf_employer', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'total_indirect_etf_employer')) {
                $table->decimal('total_indirect_etf_employer', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'total_expense_recovery')) {
                $table->decimal('total_expense_recovery', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'total_loan_deductions')) {
                $table->decimal('total_loan_deductions', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'total_advance_deductions')) {
                $table->decimal('total_advance_deductions', 15, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'increments_basic', 'budget_allowance',
                'travelling_allowance', 'cost_of_living_allowance', 'increments_allowance',
                'cost_classification'
            ]);
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn([
                'increments_basic', 'budget_allowance', 'total_base_pay',
                'travelling_allowance', 'cost_of_living_allowance', 'increments_allowance', 'total_fixed_allowance',
                'incentive_commission', 'salary_arrears_basic', 'salary_arrears_allowance', 'total_variable_pay',
                'no_pay_basic_deduction', 'no_pay_allowance_deduction', 'total_no_pay_deduction',
                'total_for_epf', 'personal_expense_recovery', 'total_deductions',
                'department_name', 'cost_classification'
            ]);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'total_epf_qualifying',
                'total_direct_salaries', 'total_indirect_salaries',
                'total_direct_epf_employer', 'total_indirect_epf_employer',
                'total_direct_etf_employer', 'total_indirect_etf_employer',
                'total_expense_recovery', 'total_loan_deductions', 'total_advance_deductions'
            ]);
        });
    }
};
