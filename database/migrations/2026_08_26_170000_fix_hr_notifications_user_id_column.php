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
        if (Schema::hasTable('hr_notifications')) {
            if (Schema::hasColumn('hr_notifications', 'employee_id') && !Schema::hasColumn('hr_notifications', 'user_id')) {
                Schema::table('hr_notifications', function (Blueprint $table) {
                    $table->renameColumn('employee_id', 'user_id');
                });
            } elseif (!Schema::hasColumn('hr_notifications', 'user_id')) {
                Schema::table('hr_notifications', function (Blueprint $table) {
                    $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hr_notifications') && Schema::hasColumn('hr_notifications', 'user_id')) {
            Schema::table('hr_notifications', function (Blueprint $table) {
                $table->renameColumn('user_id', 'employee_id');
            });
        }
    }
};
