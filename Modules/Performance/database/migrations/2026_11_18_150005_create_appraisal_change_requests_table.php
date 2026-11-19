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
        Schema::create('appraisal_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_id')->constrained('appraisals')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_at_request'); // 'Employee', 'Manager', 'HOD'
            $table->text('reason');
            $table->string('status')->default('Pending'); // 'Pending', 'Approved_Unlocked', 'Rejected'
            $table->foreignId('actioned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('action_notes')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_change_requests');
    }
};
