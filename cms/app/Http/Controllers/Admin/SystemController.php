<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemController extends Controller
{
    public function show()
    {
        $bytes = 0;
        foreach (['media', 'pages'] as $disk) {
            foreach (Storage::disk($disk)->allFiles() as $file) {
                $bytes += Storage::disk($disk)->size($file);
            }
        }

        return view('admin.system.show', [
            'php' => PHP_VERSION,
            'db' => DB::selectOne('select version() as v')->v,
            'imageDriver' => config('cms.image_driver'),
            'extensions' => collect(['gd', 'imagick', 'exif', 'fileinfo', 'intl', 'pdo_mysql', 'zip'])->mapWithKeys(fn ($e) => [$e => extension_loaded($e)]),
            'schedulerSource' => Setting::get('scheduler.last_source'),
            'schedulerLastRun' => Setting::get('scheduler.last_run_at'),
            'storageMb' => round($bytes / 1048576, 1),
        ]);
    }
}
