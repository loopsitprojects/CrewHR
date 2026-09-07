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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('req_number')->nullable();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('duration', 8, 2)->comment('Number of days');
            $table->string('status')->default('Pending'); // Pending, Step 2: Pending HR Admin, Approved, Rejected
            $table->text('reason')->nullable();
            $table->foreignId('covering_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('covering_status')->default('Pending'); // Approved, Pending, Rejected
            $table->foreignId('manager_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('manager_status')->default('Pending'); // Approved, Pending, Rejected
            $table->string('hr_status')->default('Pending'); // Approved, Pending, Rejected
            $table->date('applied_at')->nullable();
            $table->boolean('is_half_day')->default(false);
            $table->string('half_day_slot')->nullable();
            $table->boolean('is_short_leave')->default(false);
            $table->string('short_leave_slot')->nullable();
            $table->string('medical_certificate_path')->nullable();
            $table->string('project_client_name')->nullable();
            $table->boolean('is_refunded')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
