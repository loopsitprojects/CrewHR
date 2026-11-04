<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lieu_leave_claims')) {
            Schema::table('lieu_leave_claims', function (Blueprint $table) {
                if (!Schema::hasColumn('lieu_leave_claims', 'start_time')) {
                    $table->time('start_time')->nullable()->after('worked_date');
                }
                if (!Schema::hasColumn('lieu_leave_claims', 'end_time')) {
                    $table->time('end_time')->nullable()->after('start_time');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lieu_leave_claims')) {
            Schema::table('lieu_leave_claims', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('lieu_leave_claims', 'start_time')) $columns[] = 'start_time';
                if (Schema::hasColumn('lieu_leave_claims', 'end_time')) $columns[] = 'end_time';
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
