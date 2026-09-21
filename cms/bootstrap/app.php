<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('public')
                ->group(base_path('routes/public.php'));

            \Illuminate\Support\Facades\Route::middleware('web')
                ->prefix(config('cms.admin_path'))
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'session.version' => \App\Http\Middleware\EnforceSessionVersion::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->appendToGroup('web', \App\Http\Middleware\RunDueTasksAfterResponse::class);
        // Stateless group for public media and sandboxed custom pages: no
        // StartSession, EncryptCookies or CSRF, so no Set-Cookie on responses
        // that carry public cache headers. The due-tasks hook is cache-only.
        $middleware->group('public', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\RunDueTasksAfterResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Never echo the actor's typed name back into a failed form (see
        // resources/views/admin/partials/actor-name.blade.php) — merges with,
        // does not replace, Laravel's default password-field list.
        $exceptions->dontFlash(['actor_name']);
    })->create();
