<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendar_entries', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->foreignId('calendar_type_id')->constrained('calendar_types');
            $table->date('start_date')->index();
            $table->date('end_date')->nullable()->index();
            $table->string('grade_mode', 10)->default('none');
            $table->json('grade_ids')->nullable();
            $table->string('note', 500)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_entries');
    }
};
