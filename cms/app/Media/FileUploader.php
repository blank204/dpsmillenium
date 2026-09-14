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
            'image' => $this->checkImage($mime, $bytes, $name),
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
                $this->images->process($asset, $file->getRealPath());
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

    private function checkImage(string $mime, int $bytes, string $name): void
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
