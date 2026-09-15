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

    if (app()->environment('testing')) {
        Route::post('_actor-probe', function (\Tests\Support\ProbeActorRequest $request) {
            $actor = $request->actor();
            return $actor->name.'|'.$actor->role;
        });
    }

    Route::middleware('role:super_admin')->group(function () {
        if (app()->environment('testing')) {
            Route::get('_role-probe', fn () => 'ok');
        }
    });
});
