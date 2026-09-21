<?php

namespace App\Http\Controllers\Admin;

use App\Audit\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NameOnlyRequest;
use App\Models\Setting;
use App\Scheduling\DueTasks;
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

    /**
     * Run the scheduled job by hand. On shared hosting a real cron job may never be
     * set up, so IT needs a way to push scheduled posts live without shell access.
     * DueTasks locks and re-checks each post, so pressing this twice is harmless.
     */
    public function runDue(NameOnlyRequest $request, DueTasks $tasks, AuditLogger $audit)
    {
        $result = $tasks->run();
        Setting::put('scheduler.last_run_at', now()->toIso8601String());
        Setting::put('scheduler.last_source', 'manual');
        // Claim the visitor-fallback slot, or RunDueTasksAfterResponse::terminate()
        // would run the whole job again on the way out of this very request and
        // relabel this run as "visitor".
        \Illuminate\Support\Facades\Cache::put('cms:due-tasks:ran', true, 60);
        $audit->record('scheduler.run_manually', $request->actor(), null, null, $result);

        return back()->with('status', sprintf(
            'Done. %d %s published, %d %s unpinned, %d %s removed for good.',
            $result['published'], \Illuminate\Support\Str::plural('post', $result['published']),
            $result['unpinned'], \Illuminate\Support\Str::plural('post', $result['unpinned']),
            $result['purged'], \Illuminate\Support\Str::plural('post', $result['purged']),
        ));
    }
}
