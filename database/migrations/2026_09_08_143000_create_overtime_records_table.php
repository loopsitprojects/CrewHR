<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_records', function (Blueprint $table) {
            $table->id();
            $table->string('ot_number')->unique();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('ot_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('hours', 5, 2)->default(0);
            $table->string('rate_multiplier_type')->default('Standard (1.5x)'); // Standard (1.5x), Double (2.0x), Holiday (2.5x)
            $table->decimal('multiplier', 4, 2)->default(1.5);
            $table->decimal('hourly_rate', 15, 2)->default(0);
            $table->decimal('estimated_amount', 15, 2)->default(0);
            $table->text('reason')->nullable();
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected, Cancelled, Paid
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by_user_id')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('payslip_id')->nullable()->constrained('payslips')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_records');
    }
};
