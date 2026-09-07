<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->integer('month');
            $table->integer('year');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('fixed_allowance', 15, 2)->default(0);
            $table->decimal('other_allowance', 15, 2)->default(0);
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('epf_employee', 15, 2)->default(0); // 8%
            $table->decimal('epf_employer', 15, 2)->default(0); // 12%
            $table->decimal('etf_employer', 15, 2)->default(0); // 3%
            $table->decimal('apit_tax', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->string('status')->default('Processed'); // Processed, Paid
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
