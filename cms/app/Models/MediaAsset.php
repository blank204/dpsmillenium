<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    public const IMAGE_FILES = ['master.jpg', '400.webp', '400.jpg', '800.webp', '800.jpg', '1600.webp', '1600.jpg'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['crop' => 'array', 'rev' => 'integer', 'width' => 'integer', 'height' => 'integer', 'bytes' => 'integer', 'max_width' => 'integer'];
    }

    public function dir(): string
    {
        return $this->token;
    }

    public function allowedFiles(): array
    {
        return match ($this->kind) {
            'image' => self::IMAGE_FILES,
            'pdf' => ['file.pdf'],
            'video' => ['file.mp4'],
        };
    }

    public function url(string $file): string
    {
        return route('media.show', ['token' => $this->token, 'rev' => $this->rev, 'file' => $file], false);
    }
}
