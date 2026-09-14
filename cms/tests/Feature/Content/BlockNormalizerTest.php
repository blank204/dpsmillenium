<?php

namespace Tests\Feature\Content;

use App\Content\Blocks\BlockNormalizer;
use App\Models\HtmlPage;
use App\Models\MediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BlockNormalizerTest extends TestCase
{
    use RefreshDatabase;

    private function media(string $kind): MediaAsset
    {
        return MediaAsset::create(['kind' => $kind, 'token' => Str::random(32), 'original_name' => "Grade V Syllabus.{$kind}", 'mime' => 'x', 'bytes' => 10]);
    }

    private function normalize(array $blocks, bool $super = false, array $previous = []): array
    {
        return app(BlockNormalizer::class)->normalize($blocks, $super, $previous);
    }

    private function assertBlockError(array $blocks, string $key, bool $super = false, array $previous = []): void
    {
        try {
            $this->normalize($blocks, $super, $previous);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());
        }
    }

    public function test_normalizes_every_block_type(): void
    {
        $img = $this->media('image');
        $pdf = $this->media('pdf');
        $vid = $this->media('video');

        $out = $this->normalize([
            ['type' => 'text', 'html' => '<p>Hello<script>x</script></p>'],
            ['type' => 'image', 'media_id' => $img->id, 'caption' => ' Assembly '],
            ['type' => 'gallery', 'items' => [['media_id' => $img->id], ['media_id' => $img->id, 'caption' => 'b']]],
            ['type' => 'pdf', 'files' => [['media_id' => $pdf->id, 'label' => ''], ['media_id' => $pdf->id, 'label' => 'French 2L']]],
            ['type' => 'link', 'label' => 'Register', 'url' => 'https://forms.example.com/x'],
            ['type' => 'youtube', 'url' => 'https://youtu.be/dQw4w9WgXcQ', 'mode' => 'player'],
            ['type' => 'video', 'media_id' => $vid->id],
            ['type' => 'table', 'has_header' => true, 'paste' => "A\tB\n1\t2"],
        ]);

        $this->assertSame(['type' => 'text', 'version' => 1, 'html' => '<p>Hello</p>'], $out[0]);
        $this->assertSame('Assembly', $out[1]['caption']);
        $this->assertNull($out[2]['items'][0]['caption']);
        $this->assertSame('Grade V Syllabus', $out[3]['files'][0]['label']);
        $this->assertSame('dQw4w9WgXcQ', $out[5]['video_id']);
        $this->assertSame([['A', 'B'], ['1', '2']], $out[7]['rows']);
        $this->assertTrue($out[7]['has_header']);
        foreach ($out as $block) {
            $this->assertSame(1, $block['version']);
        }
    }

    public function test_unknown_type(): void
    {
        $this->assertBlockError([['type' => 'marquee']], 'blocks.0');
    }

    public function test_empty_text(): void
    {
        $this->assertBlockError([['type' => 'text', 'html' => '<p> <script>x</script></p>']], 'blocks.0');
    }

    public function test_media_must_exist_and_match_kind(): void
    {
        $pdf = $this->media('pdf');
        $this->assertBlockError([['type' => 'image', 'media_id' => $pdf->id]], 'blocks.0');
        $this->assertBlockError([['type' => 'image', 'media_id' => 999999]], 'blocks.0');
    }

    public function test_gallery_limit(): void
    {
        config(['cms.limits.gallery_images' => 2]);
        $img = $this->media('image');
        $items = array_fill(0, 3, ['media_id' => $img->id]);
        $this->assertBlockError([['type' => 'gallery', 'items' => $items]], 'blocks.0');
    }

    public function test_link_rejects_javascript_urls(): void
    {
        $this->assertBlockError([['type' => 'link', 'label' => 'x', 'url' => 'javascript:alert(1)']], 'blocks.0');
    }

    public function test_youtube_rejects_other_sites(): void
    {
        $this->assertBlockError([['type' => 'youtube', 'url' => 'https://vimeo.com/1', 'mode' => 'link']], 'blocks.0');
    }

    public function test_editor_cannot_add_html_page_block(): void
    {
        $page = HtmlPage::create(['token' => Str::random(32), 'title' => 'Seating', 'bytes' => 10]);
        $this->assertBlockError([['type' => 'html_page', 'html_page_id' => $page->id, 'mode' => 'embed']], 'blocks.0');
    }

    public function test_editor_can_keep_or_move_existing_html_page_block(): void
    {
        $page = HtmlPage::create(['token' => Str::random(32), 'title' => 'Seating', 'bytes' => 10]);
        $existing = ['type' => 'html_page', 'version' => 1, 'html_page_id' => $page->id, 'mode' => 'embed'];
        $text = ['type' => 'text', 'html' => '<p>Intro</p>'];

        $out = $this->normalize([$text, $existing], false, [$existing]);
        $this->assertSame('html_page', $out[1]['type']);

        $this->assertBlockError([['type' => 'html_page', 'html_page_id' => $page->id, 'mode' => 'link']], 'blocks.0', false, [$existing]);
    }

    public function test_super_admin_can_add_html_page_block(): void
    {
        $page = HtmlPage::create(['token' => Str::random(32), 'title' => 'Seating', 'bytes' => 10]);
        $out = $this->normalize([['type' => 'html_page', 'html_page_id' => $page->id, 'mode' => 'link']], true);
        $this->assertSame($page->id, $out[0]['html_page_id']);
    }

    public function test_table_limits_become_block_errors(): void
    {
        config(['cms.limits.table_rows' => 1]);
        $this->assertBlockError([['type' => 'table', 'paste' => "a\nb"]], 'blocks.0');
    }
}
