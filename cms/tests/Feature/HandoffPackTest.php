<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HandoffPackTest extends TestCase
{
    private function pack(string $file): string
    {
        return File::get(base_path('../docs/cms/handoff/'.$file));
    }

    public function test_every_admin_screen_is_documented(): void
    {
        $screens = collect(File::allFiles(resource_path('views/admin')))
            ->flatMap(fn ($f) => preg_match_all('/data-screen="([a-z-]+)"/', $f->getContents(), $m) ? $m[1] : [])
            ->unique()->sort()->values();

        $doc = $this->pack('01-screens.md');
        foreach ($screens as $screen) {
            $this->assertStringContainsString("`{$screen}`", $doc, "Screen {$screen} missing from 01-screens.md");
        }
    }

    public function test_limits_match_config(): void
    {
        $doc = $this->pack('02-fields-and-limits.md');
        foreach (['title' => 'Title max', 'summary' => 'Summary max', 'calendar_note' => 'Calendar note max', 'gallery_images' => 'Gallery max photos', 'table_rows' => 'Table max rows', 'table_cols' => 'Table max columns'] as $key => $label) {
            $this->assertStringContainsString($label.': '.config('cms.limits.'.$key), $doc, $label);
        }
        foreach (['image_bytes' => 'Photo max MB', 'pdf_bytes' => 'PDF max MB', 'video_bytes' => 'Video max MB'] as $key => $label) {
            $this->assertStringContainsString($label.': '.(config('cms.limits.'.$key) / 1048576), $doc, $label);
        }
    }

    public function test_sample_data_is_valid_json_with_all_sections(): void
    {
        $data = json_decode($this->pack('04-sample-data.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertEqualsCanonicalizing(['info', 'circular', 'event', 'newsletter'], array_unique(array_column($data['posts'], 'section')));
        $this->assertNotEmpty($data['calendar']);
        foreach (['text', 'image', 'gallery', 'pdf', 'link', 'youtube', 'video', 'table', 'html_page'] as $type) {
            $this->assertStringContainsString('"type": "'.$type.'"', $this->pack('04-sample-data.json'), "sample data lacks a {$type} block");
        }
    }

    public function test_wireframes_have_no_styling_decisions(): void
    {
        $html = $this->pack('05-wireframes.html');
        $this->assertDoesNotMatchRegularExpression('/font-family\s*:\s*(?!system-ui|monospace)/i', $html);
        $this->assertDoesNotMatchRegularExpression('/#(?!000\b|fff\b|888\b|ccc\b|eee\b|000000|ffffff)[0-9a-f]{3,6}\b/i', $html);
    }
}
