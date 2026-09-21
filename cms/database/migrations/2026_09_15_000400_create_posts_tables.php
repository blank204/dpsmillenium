<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('section', 20);
            $table->string('title', 150);
            $table->string('slug', 170)->nullable();
            $table->string('summary', 300)->nullable();
            $table->longText('blocks');
            $table->string('grade_mode', 10)->default('none');
            $table->json('grade_ids')->nullable();
            $table->string('status', 20)->default('draft');
            $table->dateTime('publish_at')->nullable();
            $table->dateTime('first_published_at')->nullable();
            $table->dateTime('updated_at_public')->nullable();
            $table->dateTime('sort_at')->nullable();
            $table->dateTime('remove_on')->nullable();
            $table->dateTime('pinned_until')->nullable();
            $table->string('academic_year', 7)->nullable()->index();
            $table->date('event_date_start')->nullable();
            $table->date('event_date_end')->nullable();
            $table->unsignedBigInteger('cover_media_id')->nullable();
            $table->unsignedBigInteger('newsletter_series_id')->nullable();
            $table->string('newsletter_term', 40)->nullable();
            $table->unsignedBigInteger('newsletter_pdf_media_id')->nullable();
            $table->string('newsletter_link', 2000)->nullable();
            $table->longText('search_text')->nullable();
            $table->softDeletes();
            $table->dateTime('purged_at')->nullable();
            $table->timestamps();
            $table->index(['section', 'status', 'sort_at']);
            $table->fullText(['title', 'search_text']);
        });

        Schema::create('post_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts');
            $table->unsignedInteger('version');
            $table->longText('data');
            $table->string('actor_name', 80)->nullable();
            $table->dateTime('created_at');
            $table->unique(['post_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_versions');
        Schema::dropIfExists('posts');
    }
};
