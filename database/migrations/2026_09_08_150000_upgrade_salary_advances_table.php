<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_advances', function (Blueprint $table) {
            if (!Schema::hasColumn('salary_advances', 'advance_number')) {
                $table->string('advance_number')->nullable()->after('id');
            }
            if (!Schema::hasColumn('salary_advances', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('salary_advances', 'approved_by_user_id')) {
                $table->unsignedBigInteger('approved_by_user_id')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('salary_advances', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_by_user_id');
            }
            if (!Schema::hasColumn('salary_advances', 'requested_date')) {
                $table->date('requested_date')->nullable()->after('rejection_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('salary_advances', function (Blueprint $table) {
            $table->dropColumn(['advance_number', 'approved_at', 'approved_by_user_id', 'rejection_reason', 'requested_date']);
        });
    }
};
