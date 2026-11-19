<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'personal_email')) {
                $table->string('personal_email')->nullable()->after('nic_passport');
            }
            if (!Schema::hasColumn('employees', 'company_email')) {
                $table->string('company_email')->nullable()->after('personal_email');
            }
        });

        // Initialize company_email for existing employees using their user account email
        try {
            DB::table('employees')
                ->join('users', 'employees.user_id', '=', 'users.id')
                ->whereNull('employees.company_email')
                ->update(['employees.company_email' => DB::raw('users.email')]);
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'personal_email')) {
                $table->dropColumn('personal_email');
            }
            if (Schema::hasColumn('employees', 'company_email')) {
                $table->dropColumn('company_email');
            }
        });
    }
};
