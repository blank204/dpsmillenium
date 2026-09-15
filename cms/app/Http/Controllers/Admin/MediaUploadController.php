<?php

namespace App\Http\Controllers\Admin;

use App\Audit\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaEditRequest;
use App\Media\FileUploader;
use App\Media\ImageProcessor;
use App\Media\UploadRejected;
use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaUploadController extends Controller
{
    public function upload(Request $request, FileUploader $uploader)
    {
        $request->validate(['kind' => ['required', 'in:image,pdf,video'], 'file' => ['required', 'file']]);

        try {
            $asset = $uploader->upload($request->file('file'), $request->input('kind'));
        } catch (UploadRejected $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->payload($asset));
    }

    /**
     * Crop/resize never changes an existing asset (it may be used by live or
     * duplicated posts). It makes a new asset from the cleaned master and
     * returns its id; the block switches to it and the change reaches the
     * public site only through Save/Publish, which writes a version and an
     * audit entry. The crop itself is also audited with the typed name.
     */
    public function edit(MediaEditRequest $request, MediaAsset $asset, ImageProcessor $images, AuditLogger $audit)
    {
        abort_unless($asset->kind === 'image', 404);
        $crop = $request->validated('crop');
        $maxWidth = $request->validated('max_width');

        try {
            $copy = DB::transaction(function () use ($request, $asset, $images, $audit, $crop, $maxWidth) {
                $copy = $images->derive($asset, $crop, $maxWidth);
                try {
                    $audit->record('media.cropped', $request->actor(), $copy,
                        ['media_id' => $asset->id, 'token' => $asset->token, 'crop' => $asset->crop, 'max_width' => $asset->max_width],
                        ['source_media_id' => $asset->id, 'media_id' => $copy->id, 'token' => $copy->token, 'crop' => $copy->crop, 'max_width' => $copy->max_width],
                    );
                } catch (Throwable $e) {
                    Storage::disk('media')->deleteDirectory($copy->dir());
                    throw $e;
                }

                return $copy;
            });
        } catch (UploadRejected $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->payload($copy));
    }

    private function payload(MediaAsset $asset): array
    {
        return [
            'id' => $asset->id,
            'kind' => $asset->kind,
            'original_name' => $asset->original_name,
            'preview_url' => match ($asset->kind) {
                'image' => $asset->url('400.jpg'),
                'pdf' => $asset->url('file.pdf'),
                'video' => $asset->url('file.mp4'),
            },
            'width' => $asset->width,
            'height' => $asset->height,
        ];
    }
}
