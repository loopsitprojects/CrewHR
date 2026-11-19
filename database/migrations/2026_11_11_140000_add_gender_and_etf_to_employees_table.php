<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'gender')) {
                $table->string('gender')->nullable()->after('title');
            }
            if (!Schema::hasColumn('employees', 'etf_registration_no')) {
                $table->string('etf_registration_no')->nullable()->after('epf_registration_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'gender')) {
                $table->dropColumn('gender');
            }
            if (Schema::hasColumn('employees', 'etf_registration_no')) {
                $table->dropColumn('etf_registration_no');
            }
        });
    }
};
