<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->integer('month');
            $table->integer('year');
            $table->string('cycle_name');
            $table->string('status')->default('Processed'); // Processed, Approved, Paid
            $table->unsignedBigInteger('processed_by_user_id')->nullable();
            $table->decimal('total_basic', 15, 2)->default(0);
            $table->decimal('total_allowances', 15, 2)->default(0);
            $table->decimal('total_gross', 15, 2)->default(0);
            $table->decimal('total_epf_employee', 15, 2)->default(0);
            $table->decimal('total_epf_employer', 15, 2)->default(0);
            $table->decimal('total_etf_employer', 15, 2)->default(0);
            $table->decimal('total_apit_tax', 15, 2)->default(0);
            $table->decimal('total_net_pay', 15, 2)->default(0);
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
