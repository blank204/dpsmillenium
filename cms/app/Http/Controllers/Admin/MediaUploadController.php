<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Media\FileUploader;
use App\Media\ImageProcessor;
use App\Media\UploadRejected;
use App\Models\MediaAsset;
use Illuminate\Http\Request;

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

    public function edit(Request $request, MediaAsset $asset, ImageProcessor $images)
    {
        abort_unless($asset->kind === 'image', 404);
        $data = $request->validate([
            'crop' => ['nullable', 'array'],
            'crop.x' => ['required_with:crop', 'integer', 'min:0'],
            'crop.y' => ['required_with:crop', 'integer', 'min:0'],
            'crop.w' => ['required_with:crop', 'integer', 'min:1'],
            'crop.h' => ['required_with:crop', 'integer', 'min:1'],
            'max_width' => ['nullable', 'integer', 'min:50', 'max:2560'],
        ]);

        try {
            $images->edit($asset, $data['crop'] ?? null, $data['max_width'] ?? null);
        } catch (UploadRejected $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->payload($asset->refresh()));
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
