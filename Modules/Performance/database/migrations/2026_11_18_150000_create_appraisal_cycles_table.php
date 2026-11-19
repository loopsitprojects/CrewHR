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
        Schema::create('appraisal_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('review_period')->default('Annual'); // Annual, Bi-Annual, Probation, Quarterly
            $table->date('start_date');
            $table->date('end_date');
            $table->date('self_eval_deadline')->nullable();
            $table->date('manager_eval_deadline')->nullable();
            $table->string('status')->default('Active'); // Draft, Active, Closed, Archived
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_cycles');
    }
};
