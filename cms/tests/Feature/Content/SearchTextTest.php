<?php

namespace Tests\Feature\Content;

use App\Content\SearchText;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_collects_searchable_text_from_blocks(): void
    {
        $post = Post::factory()->make([
            'title' => 'Half yearly syllabus',
            'summary' => 'For grade V',
            'newsletter_term' => null,
            'blocks' => [
                ['type' => 'text', 'version' => 1, 'html' => '<p>Bring <strong>geometry</strong> box</p>'],
                ['type' => 'image', 'version' => 1, 'media_id' => 1, 'caption' => 'Assembly photo'],
                ['type' => 'gallery', 'version' => 1, 'items' => [['media_id' => 1, 'caption' => 'Sports day']]],
                ['type' => 'pdf', 'version' => 1, 'files' => [['media_id' => 2, 'label' => 'French 2L']]],
                ['type' => 'link', 'version' => 1, 'label' => 'Register now', 'url' => 'https://x.test'],
                ['type' => 'table', 'version' => 1, 'has_header' => true, 'rows' => [['Room', 'Class'], ['101', 'VII A']]],
            ],
        ]);

        $text = SearchText::fromPost($post);

        foreach (['Half yearly syllabus', 'For grade V', 'geometry', 'Assembly photo', 'Sports day', 'French 2L', 'Register now', 'VII A'] as $needle) {
            $this->assertStringContainsString($needle, $text);
        }
        $this->assertStringNotContainsString('<strong>', $text);
    }

    public function test_table_block_with_no_rows_does_not_throw(): void
    {
        $post = Post::factory()->make([
            'title' => 'Empty table post',
            'summary' => null,
            'newsletter_term' => null,
            'blocks' => [
                ['type' => 'table', 'version' => 1, 'has_header' => false, 'rows' => []],
            ],
        ]);

        $text = SearchText::fromPost($post);

        $this->assertSame('Empty table post', $text);
    }
}
