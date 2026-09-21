<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaUploadController;
use App\Http\Controllers\Admin\PostActionController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\RecycleBinController;
use Illuminate\Support\Facades\Route;

Route::get('login', [AuthController::class, 'show'])->name('login');
Route::post('login', [AuthController::class, 'attempt'])->name('login.attempt');

Route::middleware(['auth', 'session.version'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('posts/new', [PostController::class, 'choose'])->name('posts.choose');
    Route::get('posts/new/{section}', [PostController::class, 'create'])->name('posts.create');
    Route::post('posts/new/{section}', [PostController::class, 'store'])->name('posts.store');
    Route::post('posts/preview', [PostController::class, 'preview'])->name('posts.preview');
    Route::get('posts/{post}', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('posts/{post}/duplicate', [PostActionController::class, 'duplicate'])->name('posts.duplicate');
    Route::post('posts/{post}/unschedule', [PostActionController::class, 'unschedule'])->name('posts.unschedule');
    Route::post('posts/{post}/unpin', [PostActionController::class, 'unpin'])->name('posts.unpin');

    Route::post('media', [MediaUploadController::class, 'upload'])->name('media.upload');
    Route::post('media/{asset}/edit', [MediaUploadController::class, 'edit'])->name('media.edit');

    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('calendar', [CalendarController::class, 'store'])->name('calendar.store');
    Route::put('calendar/{entry}', [CalendarController::class, 'update'])->name('calendar.update');
    Route::delete('calendar/{entry}', [CalendarController::class, 'destroy'])->name('calendar.destroy');

    Route::get('recycle-bin', [RecycleBinController::class, 'index'])->name('recycle.index');

    Route::view('how-to-post', 'admin.tutorial')->name('tutorial');

    if (app()->environment('testing')) {
        Route::post('_actor-probe', function (\Tests\Support\ProbeActorRequest $request) {
            $actor = $request->actor();
            return $actor->name.'|'.$actor->role;
        });
    }

    Route::middleware('role:super_admin')->group(function () {
        Route::get('change-log', [\App\Http\Controllers\Admin\AuditController::class, 'index'])->name('audit.index');
        Route::get('change-log/{entry}', [\App\Http\Controllers\Admin\AuditController::class, 'show'])->name('audit.show');
        Route::post('change-log/verify', [\App\Http\Controllers\Admin\AuditController::class, 'verify'])->name('audit.verify');
        Route::get('change-log.csv', [\App\Http\Controllers\Admin\AuditController::class, 'export'])->name('audit.export');

        Route::get('posts/{post}/versions', [\App\Http\Controllers\Admin\VersionController::class, 'index'])->name('posts.versions');
        Route::post('posts/{post}/versions/{version}/restore', [\App\Http\Controllers\Admin\VersionController::class, 'restore'])->name('posts.versions.restore');

        Route::post('recycle-bin/{id}/restore', [RecycleBinController::class, 'restore'])->whereNumber('id')->name('recycle.restore');

        Route::get('security', [\App\Http\Controllers\Admin\SecurityController::class, 'edit'])->name('security.edit');
        Route::post('security/editor-password', [\App\Http\Controllers\Admin\SecurityController::class, 'editorPassword'])->name('security.editor-password');
        Route::post('security/own-password', [\App\Http\Controllers\Admin\SecurityController::class, 'ownPassword'])->name('security.own-password');
        Route::post('security/logout-all', [\App\Http\Controllers\Admin\SecurityController::class, 'logoutAll'])->name('security.logout-all');
        Route::post('security/logout-session', [\App\Http\Controllers\Admin\SecurityController::class, 'logoutSession'])->name('security.logout-session');

        Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');

        Route::get('custom-pages', [\App\Http\Controllers\Admin\HtmlPageController::class, 'index'])->name('pages.index');
        Route::post('custom-pages', [\App\Http\Controllers\Admin\HtmlPageController::class, 'store'])->name('pages.store');
        Route::post('custom-pages/{page}/replace', [\App\Http\Controllers\Admin\HtmlPageController::class, 'replace'])->name('pages.replace');
        Route::delete('custom-pages/{page}', [\App\Http\Controllers\Admin\HtmlPageController::class, 'destroy'])->name('pages.destroy');

        Route::get('system', [\App\Http\Controllers\Admin\SystemController::class, 'show'])->name('system.show');
        Route::post('system/run-due', [\App\Http\Controllers\Admin\SystemController::class, 'runDue'])->name('system.run-due');

        if (app()->environment('testing')) {
            Route::get('_role-probe', fn () => 'ok');
        }
    });
});
