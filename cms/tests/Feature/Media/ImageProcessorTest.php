<?php

namespace Tests\Feature\Media;

use App\Media\FileUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Fixtures;
use Tests\TestCase;

class ImageProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
    }

    /** @return array<string, array{string}> */
    public static function drivers(): array
    {
        $drivers = ['gd' => ['gd']];
        if (extension_loaded('imagick')) {
            $drivers['imagick'] = ['imagick'];
        }
        return $drivers;
    }

    #[DataProvider('drivers')]
    public function test_strips_exif_and_generates_variants(string $driver): void
    {
        config(['cms.image_driver' => $driver]);
        $asset = app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::jpegWithExif(3000, 1500), 'IMG_0001.JPG'), 'image');

        foreach (['master.jpg', '400.webp', '400.jpg', '800.webp', '800.jpg', '1600.webp', '1600.jpg'] as $file) {
            $bytes = Storage::disk('media')->get($asset->dir().'/'.$file);
            $this->assertNotNull($bytes, "missing {$file}");
            $this->assertStringNotContainsString("Exif\0\0", $bytes, "{$file} still has EXIF");
        }

        $this->assertSame(2560, $asset->width);
        $this->assertSame(1280, $asset->height);
        [$w] = getimagesizefromstring(Storage::disk('media')->get($asset->dir().'/800.jpg'));
        $this->assertSame(800, $w);
    }

    #[DataProvider('drivers')]
    public function test_applies_exif_orientation_before_stripping(string $driver): void
    {
        config(['cms.image_driver' => $driver]);
        $asset = app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::jpegWithExif(400, 200, 6), 'phone.jpg'), 'image');

        $this->assertSame(200, $asset->width);
        $this->assertSame(400, $asset->height);
    }

    public function test_small_images_are_not_upscaled(): void
    {
        $asset = app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::jpegWithExif(300, 200), 'small.jpg'), 'image');
        [$w] = getimagesizefromstring(Storage::disk('media')->get($asset->dir().'/1600.jpg'));
        $this->assertSame(300, $w);
    }

    public function test_derive_crops_and_limits_width_into_a_new_asset(): void
    {
        $uploader = app(FileUploader::class);
        $asset = $uploader->upload(Fixtures::uploaded(Fixtures::jpegWithExif(2000, 1000), 'wide.jpg'), 'image');
        $before = Storage::disk('media')->get($asset->dir().'/1600.jpg');

        $copy = app(\App\Media\ImageProcessor::class)->derive($asset, ['x' => 100, 'y' => 100, 'w' => 1000, 'h' => 500], 600);

        $this->assertNotSame($asset->id, $copy->id);
        $this->assertNotSame($asset->token, $copy->token);
        [$w, $h] = getimagesizefromstring(Storage::disk('media')->get($copy->dir().'/1600.jpg'));
        $this->assertSame([600, 300], [$w, $h]);
        $this->assertSame(['x' => 100, 'y' => 100, 'w' => 1000, 'h' => 500], $copy->crop);
        $this->assertSame(600, $copy->max_width);
        $this->assertSame('wide.jpg', $copy->original_name);
        $this->assertNotNull(Storage::disk('media')->get($copy->dir().'/master.jpg'));

        $this->assertSame($before, Storage::disk('media')->get($asset->dir().'/1600.jpg'), 'source variants must not change');
        $this->assertNull($asset->fresh()->crop);
    }

    public function test_derive_rejects_crop_outside_photo_without_leaving_an_asset(): void
    {
        $asset = app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::jpegWithExif(400, 200), 'small.jpg'), 'image');

        try {
            app(\App\Media\ImageProcessor::class)->derive($asset, ['x' => 5000, 'y' => 0, 'w' => 10, 'h' => 10], null);
            $this->fail('expected UploadRejected');
        } catch (\App\Media\UploadRejected $e) {
            $this->assertStringContainsString('outside the photo', $e->getMessage());
        }

        $this->assertSame(1, \App\Models\MediaAsset::count());
        $this->assertCount(1, Storage::disk('media')->directories());
    }
}
