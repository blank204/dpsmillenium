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

    public function test_image_over_pixel_cap_is_rejected_before_decoding(): void
    {
        // 12000 × 6000 = 72 MP declared in a file of a few hundred bytes: decoding it
        // would need ~300 MB+ of GD memory, so it must be refused from the header alone.
        try {
            app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::pngDeclaring(12000, 6000), 'huge.png'), 'image');
            $this->fail('Expected rejection');
        } catch (UploadRejected $e) {
            $this->assertStringContainsString('12000 × 6000', $e->getMessage());
            $this->assertStringContainsString('megapixels', $e->getMessage());
        }
        $this->assertSame(0, \App\Models\MediaAsset::count());
        $this->assertSame([], Storage::disk('media')->allFiles());
    }

    public function test_pixel_cap_comes_from_config(): void
    {
        config(['cms.limits.image_pixels' => 1000]);
        $this->expectException(UploadRejected::class);
        app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::pngDeclaring(40, 40, true), 'small.png'), 'image');
    }

    public function test_image_under_pixel_cap_is_accepted(): void
    {
        $asset = app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::pngDeclaring(40, 30, true), 'ok.png'), 'image');
        $this->assertSame([40, 30], [$asset->width, $asset->height]);
    }

    public function test_corrupt_jpeg_gets_plain_message_and_leaves_nothing(): void
    {
        try {
            app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::corruptJpeg(), 'broken.jpg'), 'image');
            $this->fail('Expected rejection');
        } catch (UploadRejected $e) {
            $this->assertStringContainsString('"broken.jpg" could not be read', $e->getMessage());
        }
        $this->assertSame(0, \App\Models\MediaAsset::count());
        $this->assertSame([], Storage::disk('media')->allFiles());
    }

    public function test_image_whose_header_reads_but_data_is_corrupt_gets_plain_message_and_leaves_nothing(): void
    {
        try {
            app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::pngDeclaring(64, 64), 'damaged.png'), 'image');
            $this->fail('Expected rejection');
        } catch (UploadRejected $e) {
            $this->assertStringContainsString('"damaged.png" could not be read', $e->getMessage());
        }
        $this->assertSame(0, \App\Models\MediaAsset::count());
        $this->assertSame([], Storage::disk('media')->allFiles());
        $this->assertSame([], Storage::disk('media')->directories());
    }

    public function test_mp4_upload(): void
    {
        $asset = app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::mp4(), 'clip.mp4'), 'video');
        $this->assertSame('video', $asset->kind);
        Storage::disk('media')->assertExists($asset->dir().'/file.mp4');
    }
}
