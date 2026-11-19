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
        Schema::create('appraisal_criteria_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_id')->constrained('appraisals')->cascadeOnDelete();
            $table->string('criteria_name');
            $table->text('criteria_description')->nullable();
            $table->decimal('weightage', 5, 2)->default(0); // e.g. 20.00 %
            $table->unsignedTinyInteger('self_rating')->nullable(); // 1 - 5
            $table->text('self_comments')->nullable();
            $table->unsignedTinyInteger('manager_rating')->nullable(); // 1 - 5
            $table->text('manager_comments')->nullable();
            $table->unsignedTinyInteger('final_rating')->nullable(); // 1 - 5
            $table->decimal('weighted_score', 4, 2)->default(0.00); // final_rating * (weightage / 100)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_criteria_scores');
    }
};
