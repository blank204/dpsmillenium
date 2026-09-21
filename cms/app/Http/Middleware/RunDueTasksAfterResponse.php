<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Scheduling\DueTasks;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RunDueTasksAfterResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! Cache::add('cms:due-tasks:ran', true, 60)) {
            return;
        }

        try {
            app(DueTasks::class)->run();
            Setting::put('scheduler.last_run_at', now()->toIso8601String());
            Setting::put('scheduler.last_source', 'visitor');
        } catch (Throwable $e) {
            Log::error('Visitor-triggered due tasks failed', ['exception' => $e]);
        }
    }
}
