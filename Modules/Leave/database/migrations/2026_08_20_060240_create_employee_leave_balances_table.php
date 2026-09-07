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
        Schema::create('employee_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->decimal('allocated', 8, 2)->default(0);
            $table->decimal('used', 8, 2)->default(0);
            $table->decimal('carried_forward', 8, 2)->default(0);
            $table->decimal('allocated_days', 8, 2)->default(0);
            $table->decimal('used_days', 8, 2)->default(0);
            $table->integer('year')->default(2026);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leave_balances');
    }
};
