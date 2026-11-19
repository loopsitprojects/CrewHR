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
        Schema::create('appraisals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('appraisal_cycles')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('appraisal_templates')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('reporting_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('hod_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('workflow_type')->default('three_tier'); // 'two_tier' (Emp -> HOD) or 'three_tier' (Emp -> Manager -> HOD)
            $table->string('status')->default('pending_self_eval'); 
            // pending_self_eval, submitted_self_eval, pending_manager_eval, submitted_manager_eval, pending_hod_eval, completed
            $table->boolean('is_locked')->default(false);
            $table->timestamp('self_submitted_at')->nullable();
            $table->timestamp('manager_submitted_at')->nullable();
            $table->timestamp('hod_submitted_at')->nullable();
            
            // Scoring
            $table->decimal('total_weighted_score', 4, 2)->default(0.00); // 0.00 to 5.00
            $table->string('performance_band')->nullable(); // Exceptional, Exceeds Expectations, Meets Expectations, Needs Improvement, Unsatisfactory
            
            // Feedback
            $table->text('general_employee_comments')->nullable();
            $table->text('general_manager_comments')->nullable();
            $table->text('general_hod_comments')->nullable();
            
            // Increment & Promotion Section
            $table->decimal('increment_percentage', 5, 2)->default(0.00);
            $table->boolean('is_promoted')->default(false);
            $table->foreignId('new_designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->date('effective_date')->nullable();
            $table->boolean('is_increment_visible_to_employee')->default(false);
            $table->boolean('is_increment_visible_to_hod')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisals');
    }
};
