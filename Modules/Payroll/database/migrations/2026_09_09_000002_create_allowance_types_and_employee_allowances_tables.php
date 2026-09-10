<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allowance_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_epf_liable')->default(false);
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->string('status')->default('Active'); // Active, Inactive
            $table->timestamps();
        });

        Schema::create('employee_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('allowance_type_id')->constrained('allowance_types')->onDelete('cascade');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });

        // Seed initial default allowance types
        $defaultTypes = [
            ['name' => 'Travelling Allowance', 'code' => 'TRAV', 'is_epf_liable' => false, 'default_amount' => 15000, 'status' => 'Active'],
            ['name' => 'Cost of Living - COLA', 'code' => 'COLA', 'is_epf_liable' => false, 'default_amount' => 10000, 'status' => 'Active'],
            ['name' => 'Increments Allowance', 'code' => 'INC_ALLW', 'is_epf_liable' => false, 'default_amount' => 0, 'status' => 'Active'],
            ['name' => 'Other Fixed Allowance', 'code' => 'OTHER_FIXED', 'is_epf_liable' => false, 'default_amount' => 0, 'status' => 'Active'],
            ['name' => 'Fuel & Transport Allowance', 'code' => 'FUEL', 'is_epf_liable' => false, 'default_amount' => 20000, 'status' => 'Active'],
            ['name' => 'Telephone & Internet Allowance', 'code' => 'COMM', 'is_epf_liable' => false, 'default_amount' => 5000, 'status' => 'Active'],
            ['name' => 'Housing / Accommodation Allowance', 'code' => 'HOUSE', 'is_epf_liable' => false, 'default_amount' => 25000, 'status' => 'Active'],
            ['name' => 'Attendance Allowance', 'code' => 'ATTN', 'is_epf_liable' => false, 'default_amount' => 10000, 'status' => 'Active'],
        ];

        foreach ($defaultTypes as $type) {
            DB::table('allowance_types')->insert(array_merge($type, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_allowances');
        Schema::dropIfExists('allowance_types');
    }
};
