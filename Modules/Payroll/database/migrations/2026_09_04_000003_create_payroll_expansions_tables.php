<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add payment_method and staff_category to employees table
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'payment_method')) {
                $table->string('payment_method')->default('Bank Transfer')->after('system_role'); // Bank Transfer, Cash, Cheque
            }
            if (!Schema::hasColumn('employees', 'staff_category')) {
                $table->string('staff_category')->default('Executive')->after('job_category'); // Executive, Non-Executive, Shift Staff, Management, Contract
            }
        });

        // 2. Create employee_loans table
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('loan_title');
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('monthly_installment', 15, 2);
            $table->decimal('remaining_balance', 15, 2);
            $table->string('status')->default('Active'); // Active, Completed
            $table->date('start_date')->useCurrent();
            $table->timestamps();
        });

        // 3. Create salary_advances table
        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->integer('month');
            $table->integer('year');
            $table->string('reason')->nullable();
            $table->string('status')->default('Pending'); // Pending, Deducted
            $table->timestamps();
        });

        // 4. Expand payslips table
        Schema::table('payslips', function (Blueprint $table) {
            $table->string('staff_category')->nullable()->after('employee_id');
            $table->string('payment_method')->default('Bank Transfer')->after('staff_category');
            $table->decimal('ot_hours', 8, 2)->default(0)->after('other_allowance');
            $table->decimal('ot_amount', 15, 2)->default(0)->after('ot_hours');
            $table->decimal('shift_allowance', 15, 2)->default(0)->after('ot_amount');
            $table->decimal('performance_incentive', 15, 2)->default(0)->after('shift_allowance');
            $table->decimal('no_pay_days', 8, 2)->default(0)->after('gross_salary');
            $table->decimal('no_pay_deduction', 15, 2)->default(0)->after('no_pay_days');
            $table->decimal('loan_installment', 15, 2)->default(0)->after('apit_tax');
            $table->decimal('salary_advance', 15, 2)->default(0)->after('loan_installment');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn([
                'staff_category', 'payment_method', 'ot_hours', 'ot_amount',
                'shift_allowance', 'performance_incentive', 'no_pay_days',
                'no_pay_deduction', 'loan_installment', 'salary_advance'
            ]);
        });
        Schema::dropIfExists('salary_advances');
        Schema::dropIfExists('employee_loans');
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'staff_category']);
        });
    }
};
