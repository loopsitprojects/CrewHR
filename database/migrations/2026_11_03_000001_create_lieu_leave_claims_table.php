<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lieu_leave_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('worked_date');
            $table->string('day_type')->default('Weekend'); // Weekend, Public Holiday, Company Holiday
            $table->string('duration_type')->default('full_day'); // half_day, full_day
            $table->decimal('hours_worked', 4, 2)->default(8.00); // 4.00 or 8.00
            $table->decimal('credit_days', 3, 2)->default(1.00); // 0.50 or 1.00
            $table->text('reason')->nullable();
            $table->string('status')->default('Pending Manager Approval'); // Pending Manager Approval, Approved, Rejected, Cancelled
            $table->foreignId('manager_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_used')->default(false);
            $table->foreignId('leave_request_id')->nullable()->constrained('leave_requests')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lieu_leave_claims');
    }
};
