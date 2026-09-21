<?php

namespace Tests\Feature\Media;

use App\Media\FileUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Fixtures;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_serves_variant_with_safe_headers(): void
    {
        Storage::fake('media');
        $asset = app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::jpegWithExif(500, 300), 'a.jpg'), 'image');

        $this->get($asset->url('400.webp'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_pdf_inline_and_download(): void
    {
        Storage::fake('media');
        $asset = app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::pdf(), 'Grade V Syllabus.pdf'), 'pdf');

        $this->get($asset->url('file.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $disposition = $this->get($asset->url('file.pdf').'?download=1')->headers->get('Content-Disposition');
        $this->assertStringStartsWith('attachment;', $disposition);
        $this->assertStringContainsString('Grade V Syllabus.pdf', $disposition);
    }

    public function test_wrong_token_or_file_is_404(): void
    {
        Storage::fake('media');
        $asset = app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::pdf(), 'x.pdf'), 'pdf');

        $this->get('/media/'.str_repeat('a', 32).'/'.$asset->rev.'/file.pdf')->assertNotFound();
        $this->get('/media/'.$asset->token.'/'.$asset->rev.'/file.mp4')->assertNotFound();
        $this->get('/media/'.$asset->token.'/'.$asset->rev.'/../../.env')->assertNotFound();
    }
}
