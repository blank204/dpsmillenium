<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value');
            $table->timestamps();
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('label', 30)->unique();
            $table->unsignedInteger('position');
            $table->boolean('hidden')->default(false);
            $table->timestamps();
        });

        Schema::create('calendar_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->unique();
            $table->boolean('hidden')->default(false);
            $table->timestamps();
        });

        Schema::create('newsletter_series', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->boolean('hidden')->default(false);
            $table->timestamps();
        });

        $now = now();
        foreach (['Pre-KG', 'KG1', 'KG2', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'] as $i => $label) {
            DB::table('grades')->insert(['label' => $label, 'position' => ($i + 1) * 10, 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach (['Holiday', 'Exam'] as $name) {
            DB::table('calendar_types')->insert(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach ([
            'homepage_count.info' => 5,
            'homepage_count.circular' => 4,
            'homepage_count.event' => 8,
            'homepage_count.newsletter' => 4,
            'academic_year_start_month' => 4,
        ] as $key => $value) {
            DB::table('settings')->insert(['key' => $key, 'value' => json_encode($value), 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_series');
        Schema::dropIfExists('calendar_types');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('settings');
    }
};
