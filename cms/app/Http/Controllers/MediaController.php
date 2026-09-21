<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function __invoke(Request $request, string $token, int $rev, string $file)
    {
        $asset = MediaAsset::where('token', $token)->first();
        abort_if($asset === null || ! in_array($file, $asset->allowedFiles(), true), 404);

        $disk = Storage::disk('media');
        $path = $asset->dir().'/'.$file;
        abort_unless($disk->exists($path), 404);

        $type = match (pathinfo($file, PATHINFO_EXTENSION)) {
            'jpg' => 'image/jpeg',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
        };

        $headers = [
            'Content-Type' => $type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $rev === $asset->rev ? 'public, max-age=31536000, immutable' : 'no-cache',
        ];

        $safeName = preg_replace('/[^\w\-. ()]/u', '_', $asset->original_name) ?: 'file';
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response()->file($disk->path($path), $headers + [
            'Content-Disposition' => $disposition.'; filename="'.$safeName.'"',
        ]);
    }
}
