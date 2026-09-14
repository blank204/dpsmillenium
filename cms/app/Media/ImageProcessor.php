<?php

namespace App\Media;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class ImageProcessor
{
    public const MASTER_MAX = 2560;
    public const WIDTHS = [400, 800, 1600];

    private function manager(): ImageManager
    {
        return config('cms.image_driver') === 'imagick'
            ? ImageManager::imagick(autoOrientation: false)
            : ImageManager::gd(autoOrientation: false);
    }

    public function process(MediaAsset $asset, string $sourcePath): void
    {
        $image = $this->manager()->read($sourcePath);
        $image->orient();
        $image->scaleDown(self::MASTER_MAX, self::MASTER_MAX);
        $this->strip($image);

        $disk = Storage::disk('media');
        $disk->put($asset->dir().'/master.jpg', (string) $image->toJpeg(88));

        $asset->width = $image->width();
        $asset->height = $image->height();
        $asset->save();

        $this->writeVariants($asset, $image);
    }

    public function edit(MediaAsset $asset, ?array $crop, ?int $maxWidth): void
    {
        $image = $this->manager()->read(Storage::disk('media')->path($asset->dir().'/master.jpg'));

        if ($crop !== null) {
            $x = max(0, (int) $crop['x']);
            $y = max(0, (int) $crop['y']);
            $w = min((int) $crop['w'], $image->width() - $x);
            $h = min((int) $crop['h'], $image->height() - $y);
            if ($w < 1 || $h < 1) {
                throw new UploadRejected('The crop area is outside the photo. Try selecting the area again.');
            }
            $image->crop($w, $h, $x, $y);
            $crop = ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h];
        }

        if ($maxWidth !== null) {
            $image->scaleDown(width: max(50, $maxWidth));
        }

        $asset->crop = $crop;
        $asset->max_width = $maxWidth;
        $asset->rev = $asset->rev + 1;
        $asset->save();

        $this->writeVariants($asset, $image);
    }

    private function writeVariants(MediaAsset $asset, ImageInterface $source): void
    {
        $disk = Storage::disk('media');
        foreach (self::WIDTHS as $width) {
            $variant = clone $source;
            $variant->scaleDown(width: $width);
            $this->strip($variant);
            $disk->put($asset->dir()."/{$width}.webp", (string) $variant->toWebp(80));
            $disk->put($asset->dir()."/{$width}.jpg", (string) $variant->toJpeg(82));
        }
    }

    private function strip(ImageInterface $image): void
    {
        $native = $image->core()->native();
        if ($native instanceof \Imagick) {
            $native->stripImage();
        } elseif (is_iterable($native)) {
            foreach ($native as $frame) {
                if ($frame instanceof \Imagick) {
                    $frame->stripImage();
                }
            }
        }
        // GD never writes EXIF when encoding, so nothing to do for GD.
    }
}
