<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add Night Shift Recovery columns to leave_requests
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('night_sign_out_time')->nullable()->after('short_leave_slot');
            $table->string('night_report_time')->nullable()->after('night_sign_out_time');
            $table->string('concession_category')->nullable()->after('night_report_time');
        });

        // 2. Insert or update the NIGHT_SHIFT_RECOVERY leave type
        $existing = DB::table('leave_types')->where('code', 'NIGHT_SHIFT_RECOVERY')->first();
        if (!$existing) {
            DB::table('leave_types')->insert([
                'name' => 'Night Shift Recovery',
                'code' => 'NIGHT_SHIFT_RECOVERY',
                'days' => 0,
                'is_active' => true,
                'description' => 'Late stay concession for working late at night, subject to HOD approval. Adjusts reporting time or grants half/full day off without affecting attendance.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['night_sign_out_time', 'night_report_time', 'concession_category']);
        });

        DB::table('leave_types')->where('code', 'NIGHT_SHIFT_RECOVERY')->delete();
    }
};
