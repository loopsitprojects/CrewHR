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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('employee_id_number')->unique();
            
            // Personal Info
            $table->string('title')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nic_passport')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('profile_picture')->nullable();

            // Employment Details
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('designation_id')->constrained('designations');
            $table->string('system_role')->default('Employee');
            $table->foreignId('reporting_person_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('joined_date')->nullable();
            $table->string('epf_registration_no')->nullable();
            $table->string('job_category')->nullable();

            // Career Growth / Promotion (Optional)
            $table->decimal('increment_amount', 10, 2)->default(0);
            $table->string('promotion_designation')->nullable();
            $table->date('promotion_date')->nullable();

            // Financial Details
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('fixed_allowance', 10, 2)->default(0);
            $table->decimal('other_allowance', 10, 2)->default(0);
            $table->decimal('apit_tax', 10, 2)->default(0);
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder_name')->nullable();

            // Qualifications
            $table->text('higher_education')->nullable();
            $table->text('professional_qualifications')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
