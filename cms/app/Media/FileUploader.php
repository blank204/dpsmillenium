<?php

namespace App\Media;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploader
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(private readonly ImageProcessor $images) {}

    public function upload(UploadedFile $file, string $kind): MediaAsset
    {
        if (! $file->isValid()) {
            throw new UploadRejected('The upload did not finish. Check your connection and try again.');
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        $bytes = (int) $file->getSize();
        $name = mb_substr($file->getClientOriginalName(), 0, 255);

        match ($kind) {
            'image' => $this->checkImage($mime, $bytes, $name, $file->getRealPath()),
            'pdf' => $this->checkPdf($mime, $bytes, $name, $file->getRealPath()),
            'video' => $this->checkVideo($mime, $bytes, $name, $file->getRealPath()),
            default => throw new UploadRejected('Unsupported upload type.'),
        };

        return DB::transaction(function () use ($file, $kind, $mime, $bytes, $name) {
            $asset = MediaAsset::create([
                'kind' => $kind,
                'token' => Str::random(32),
                'original_name' => $name,
                'mime' => $kind === 'image' ? 'image/jpeg' : $mime,
                'bytes' => $bytes,
            ]);

            if ($kind === 'image') {
                try {
                    $this->images->process($asset, $file->getRealPath());
                } catch (\Throwable $e) {
                    // Decoder errors (truncated JPEG, damaged PNG, unsupported HEIC) must
                    // become a plain-language message, never a 500. The row rolls back with
                    // the transaction; remove any variants already written.
                    Storage::disk('media')->deleteDirectory($asset->dir());
                    report($e);
                    throw new UploadRejected($this->unreadable($name));
                }
            } else {
                $target = $kind === 'pdf' ? 'file.pdf' : 'file.mp4';
                Storage::disk('media')->putFileAs($asset->dir(), $file, $target);
            }

            return $asset->refresh();
        });
    }

    private function mb(int $bytes): string
    {
        return rtrim(rtrim(number_format($bytes / 1048576, 1), '0'), '.').' MB';
    }

    private function unreadable(string $name): string
    {
        return "\"{$name}\" could not be read. It may be damaged or in an unusual format. Save it as JPG and try again.";
    }

    /**
     * Read width/height from the file header only (no decoding) and refuse photos
     * whose decoded size would exhaust PHP memory, which is an uncatchable fatal error.
     */
    private function checkImageDimensions(string $mime, string $name, string $path): void
    {
        $size = @getimagesize($path);
        [$width, $height] = is_array($size) ? [(int) $size[0], (int) $size[1]] : [0, 0];

        if (($width < 1 || $height < 1) && ($mime === 'image/heic' || $mime === 'image/heif') && class_exists(\Imagick::class)) {
            try {
                $probe = new \Imagick();
                $probe->pingImage($path);
                [$width, $height] = [$probe->getImageWidth(), $probe->getImageHeight()];
                $probe->clear();
            } catch (\Throwable) {
                [$width, $height] = [0, 0];
            }
        }

        if ($width < 1 || $height < 1) {
            throw new UploadRejected($this->unreadable($name));
        }

        $pixels = $width * $height;

        /*
         * A damaged JPEG can still carry bytes that parse as a plausible SOF marker, so
         * getimagesize() reports a picture that is not really there. Telling a teacher
         * their 2 KB file "is 258 megapixels" sends them off resizing a photo that was
         * simply corrupt. Even the most aggressive JPEG rarely clears ~50 pixels per
         * byte, so a thousand-fold above that means the header is lying, not that the
         * photo is large. JPEG only: the ratio is meaningless for formats that can
         * legitimately store a huge flat image in very few bytes.
         */
        $bytes = max(1, (int) @filesize($path));
        if ($mime === 'image/jpeg' && $pixels / $bytes > 1000) {
            throw new UploadRejected($this->unreadable($name));
        }

        $cap = (int) config('cms.limits.image_pixels');
        $fitsMemory = $this->decodeFitsMemory($pixels);
        if ($pixels > $cap || ! $fitsMemory) {
            $mp = rtrim(rtrim(number_format($pixels / 1_000_000, 1), '0'), '.');
            $limit = rtrim(rtrim(number_format(min($cap, $pixels) / 1_000_000, 1), '0'), '.');
            $why = $pixels > $cap ? "The limit is {$limit} megapixels" : 'That is too large for this server to process';
            throw new UploadRejected("\"{$name}\" is {$width} × {$height} pixels ({$mp} megapixels). {$why}. Resize it (for example, share it from your phone as a smaller photo) and upload again.");
        }
    }

    private function decodeFitsMemory(int $pixels): bool
    {
        $limit = trim((string) ini_get('memory_limit'));
        if ($limit === '' || $limit === '-1') {
            return true;
        }
        $bytes = (int) $limit;
        $bytes *= match (strtolower(substr($limit, -1))) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        // ~5 bytes per decoded pixel, plus headroom for the scaled master and variants.
        return $pixels * 5 + 64 * 1024 * 1024 < $bytes - memory_get_usage(true);
    }

    private function checkImage(string $mime, int $bytes, string $name, string $path): void
    {
        if ($mime === 'image/heic' || $mime === 'image/heif') {
            if (config('cms.image_driver') !== 'imagick') {
                throw new UploadRejected("\"{$name}\" is an iPhone HEIC photo. Please send it as JPG (on iPhone: Settings → Camera → Formats → Most Compatible) and upload again.");
            }
        } elseif (! in_array($mime, self::IMAGE_MIMES, true)) {
            throw new UploadRejected("\"{$name}\" is not a photo this site can use. Upload a JPG, PNG or WebP image.");
        }
        $limit = config('cms.limits.image_bytes');
        if ($bytes > $limit) {
            throw new UploadRejected("\"{$name}\" is {$this->mb($bytes)}. The limit is {$this->mb($limit)} per photo.");
        }
        $this->checkImageDimensions($mime, $name, $path);
    }

    private function checkPdf(string $mime, int $bytes, string $name, string $path): void
    {
        $head = (string) file_get_contents($path, false, null, 0, 5);
        if ($mime !== 'application/pdf' || $head !== '%PDF-') {
            throw new UploadRejected("\"{$name}\" is not a PDF file. Save it as PDF and upload again.");
        }
        $limit = config('cms.limits.pdf_bytes');
        if ($bytes > $limit) {
            throw new UploadRejected("\"{$name}\" is {$this->mb($bytes)}. The limit is {$this->mb($limit)} per PDF. Try compressing it.");
        }
    }

    private function checkVideo(string $mime, int $bytes, string $name, string $path): void
    {
        $box = (string) file_get_contents($path, false, null, 4, 4);
        if ($box !== 'ftyp' || ! in_array($mime, ['video/mp4', 'application/octet-stream'], true)) {
            throw new UploadRejected("\"{$name}\" is not an MP4 video. Upload an MP4, or put the video on YouTube and add a YouTube block.");
        }
        $limit = config('cms.limits.video_bytes');
        if ($bytes > $limit) {
            throw new UploadRejected("This video is {$this->mb($bytes)}. The limit is {$this->mb($limit)}. Upload it to YouTube and add a YouTube block instead.");
        }
    }
}
