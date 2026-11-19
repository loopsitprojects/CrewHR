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
        Schema::create('appraisal_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_id')->nullable()->constrained('appraisals')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('letter_type'); // increment, promotion, warning, confirmation
            $table->string('reference_no')->unique();
            $table->string('title');
            $table->longText('template_content');
            $table->string('status')->default('draft'); // draft, pending_signatory, signed_by_management, acknowledged_by_employee
            $table->string('signatory_name')->nullable();
            $table->foreignId('signatory_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signatory_signed_at')->nullable();
            $table->longText('signatory_signature_data')->nullable();
            $table->timestamp('employee_acknowledged_at')->nullable();
            $table->longText('employee_signature_data')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_letters');
    }
};
