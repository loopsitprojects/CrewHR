<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable();
            $table->string('color')->default('#3b82f6');
            $table->string('bg_color')->nullable();
            $table->string('border_color')->nullable();
            $table->string('text_color')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_core')->default(false);
            $table->timestamps();
        });

        // Seed initial default day types
        $defaults = [
            [
                'name' => 'Additional Company holiday',
                'code' => 'COMPANY',
                'color' => '#9333ea',
                'bg_color' => '#f3e8ff',
                'border_color' => '#d8b4fe',
                'text_color' => '#6b21a8',
                'is_core' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mercantile Holiday',
                'code' => 'MERCANTILE',
                'color' => '#2563eb',
                'bg_color' => '#dbeafe',
                'border_color' => '#93c5fd',
                'text_color' => '#1e40af',
                'is_core' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Poya Day',
                'code' => 'POYA',
                'color' => '#854d0e',
                'bg_color' => '#fef3c7',
                'border_color' => '#fde68a',
                'text_color' => '#78350f',
                'is_core' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Public Holiday',
                'code' => 'PUBLIC',
                'color' => '#db2777',
                'bg_color' => '#fce7f3',
                'border_color' => '#fbcfe8',
                'text_color' => '#9d174d',
                'is_core' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        DB::table('day_types')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('day_types');
    }
};
