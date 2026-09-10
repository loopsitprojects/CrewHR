<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date');
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            $table->decimal('total_hours', 5, 2)->default(0);
            $table->decimal('regular_hours', 5, 2)->default(0);
            $table->decimal('early_ot_hours', 5, 2)->default(0);
            $table->decimal('late_ot_hours', 5, 2)->default(0);
            $table->decimal('total_ot_hours', 5, 2)->default(0);
            $table->string('status')->default('Present'); // Present, Late, Half Day, Absent
            $table->string('clock_in_ip')->nullable();
            $table->string('clock_out_ip')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
