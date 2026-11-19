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
        Schema::create('appraisal_template_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('appraisal_templates')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('weightage', 5, 2)->default(0); // e.g. 20.00 %
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_template_criteria');
    }
};
