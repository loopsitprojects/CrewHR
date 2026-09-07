<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create loan_types table
        Schema::create('loan_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Distress Loan, Housing Loan, Personal Loan, Festival Advance, Vehicle Loan
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('max_amount', 15, 2)->default(500000);
            $table->decimal('interest_rate_annual', 5, 2)->default(0); // 0% for interest-free distress loans
            $table->integer('max_repayment_months')->default(60);
            $table->string('status')->default('Active'); // Active, Inactive
            $table->timestamps();
        });

        // 2. Upgrade employee_loans table
        Schema::table('employee_loans', function (Blueprint $table) {
            $table->string('loan_number')->nullable()->after('id');
            $table->foreignId('loan_type_id')->nullable()->constrained('loan_types')->nullOnDelete()->after('employee_id');
            $table->decimal('interest_rate', 5, 2)->default(0)->after('principal_amount');
            $table->integer('repayment_months')->default(12)->after('interest_rate');
            $table->decimal('total_paid', 15, 2)->default(0)->after('remaining_balance');
            $table->text('purpose')->nullable()->after('total_paid');
            $table->timestamp('applied_at')->useCurrent()->after('purpose');
            $table->timestamp('approved_at')->nullable()->after('applied_at');
            $table->unsignedBigInteger('approved_by_user_id')->nullable()->after('approved_at');
        });

        // 3. Create loan_repayments table (Transaction Ledger)
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_loan_id')->constrained('employee_loans')->onDelete('cascade');
            $table->foreignId('payslip_id')->nullable()->constrained('payslips')->nullOnDelete();
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('principal_paid', 15, 2)->default(0);
            $table->decimal('interest_paid', 15, 2)->default(0);
            $table->decimal('remaining_balance_after', 15, 2);
            $table->string('repayment_type')->default('Payroll Auto-Deduction'); // Payroll Auto-Deduction, Manual Cash/Bank Payment
            $table->timestamp('paid_date')->useCurrent();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
        Schema::table('employee_loans', function (Blueprint $table) {
            $table->dropForeign(['loan_type_id']);
            $table->dropColumn(['loan_number', 'loan_type_id', 'interest_rate', 'repayment_months', 'total_paid', 'purpose', 'applied_at', 'approved_at', 'approved_by_user_id']);
        });
        Schema::dropIfExists('loan_types');
    }
};
