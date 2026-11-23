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
        Schema::table('appraisals', function (Blueprint $table) {
            $table->timestamp('unlocked_until')->nullable()->after('is_locked');
            $table->boolean('is_override_unlocked')->default(false)->after('unlocked_until');
            $table->foreignId('unlocked_by_user_id')->nullable()->after('is_override_unlocked')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appraisals', function (Blueprint $table) {
            $table->dropForeign(['unlocked_by_user_id']);
            $table->dropColumn(['unlocked_until', 'is_override_unlocked', 'unlocked_by_user_id']);
        });
    }
};
