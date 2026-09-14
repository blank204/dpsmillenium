<?php

namespace Tests\Feature\Media;

use App\Media\FileUploader;
use App\Media\UploadRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Fixtures;
use Tests\TestCase;

class FileUploaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
    }

    public function test_pdf_upload(): void
    {
        $asset = app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::pdf(), 'Syllabus.pdf'), 'pdf');
        $this->assertSame('pdf', $asset->kind);
        $this->assertSame('application/pdf', $asset->mime);
        Storage::disk('media')->assertExists($asset->dir().'/file.pdf');
        $this->assertSame(32, strlen($asset->token));
    }

    public function test_disguised_file_is_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fx');
        file_put_contents($path, '<?php echo "hi";');
        $this->expectException(UploadRejected::class);
        app(FileUploader::class)->upload(Fixtures::uploaded($path, 'notes.pdf'), 'pdf');
    }

    public function test_image_named_as_jpg_but_is_php_is_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fx');
        file_put_contents($path, '<?php system($_GET["c"]);');
        $this->expectException(UploadRejected::class);
        app(FileUploader::class)->upload(Fixtures::uploaded($path, 'photo.jpg'), 'image');
    }

    public function test_oversized_pdf_message(): void
    {
        config(['cms.limits.pdf_bytes' => 1024]);
        try {
            app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::pdf(4096), 'big.pdf'), 'pdf');
            $this->fail('Expected rejection');
        } catch (UploadRejected $e) {
            $this->assertStringContainsString('The limit is', $e->getMessage());
        }
    }

    public function test_oversized_video_suggests_youtube(): void
    {
        config(['cms.limits.video_bytes' => 1024]);
        try {
            app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::mp4(4096), 'clip.mp4'), 'video');
            $this->fail('Expected rejection');
        } catch (UploadRejected $e) {
            $this->assertStringContainsString('YouTube', $e->getMessage());
        }
    }

    public function test_mp4_upload(): void
    {
        $asset = app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::mp4(), 'clip.mp4'), 'video');
        $this->assertSame('video', $asset->kind);
        Storage::disk('media')->assertExists($asset->dir().'/file.mp4');
    }
}
