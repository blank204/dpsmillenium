<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('html_pages', function (Blueprint $table) {
            $table->id();
            $table->char('token', 32)->unique();
            $table->string('title', 150);
            $table->unsignedBigInteger('bytes');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('html_pages');
    }
};
