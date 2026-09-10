<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('day_types') && !Schema::hasColumn('day_types', 'is_mercantile')) {
            Schema::table('day_types', function (Blueprint $table) {
                $table->boolean('is_mercantile')->default(true)->after('is_core');
            });
        }

        if (Schema::hasTable('holidays') && !Schema::hasColumn('holidays', 'is_mercantile')) {
            Schema::table('holidays', function (Blueprint $table) {
                $table->boolean('is_mercantile')->default(true)->after('category');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('day_types') && Schema::hasColumn('day_types', 'is_mercantile')) {
            Schema::table('day_types', function (Blueprint $table) {
                $table->dropColumn('is_mercantile');
            });
        }

        if (Schema::hasTable('holidays') && Schema::hasColumn('holidays', 'is_mercantile')) {
            Schema::table('holidays', function (Blueprint $table) {
                $table->dropColumn('is_mercantile');
            });
        }
    }
};
