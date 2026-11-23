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
        Schema::table('appraisal_cycles', function (Blueprint $table) {
            $table->string('cycle_type')->default('year_end')->after('review_period'); // mid_year, year_end, probation, other
            $table->string('financial_year')->nullable()->after('cycle_type'); // e.g. FY 2026/2027
            $table->string('performance_period')->nullable()->after('financial_year'); // e.g. April to September, October to March
            $table->date('performance_period_start')->nullable()->after('performance_period');
            $table->date('performance_period_end')->nullable()->after('performance_period_start');
            $table->boolean('allow_increments')->default(true)->after('performance_period_end');
            $table->text('focus_areas')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appraisal_cycles', function (Blueprint $table) {
            $table->dropColumn([
                'cycle_type',
                'financial_year',
                'performance_period',
                'performance_period_start',
                'performance_period_end',
                'allow_increments',
                'focus_areas',
            ]);
        });
    }
};
