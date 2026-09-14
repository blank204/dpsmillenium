<?php

use App\Http\Controllers\Admin\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('login', [AuthController::class, 'show'])->name('login');
Route::post('login', [AuthController::class, 'attempt'])->name('login.attempt');

Route::middleware(['auth', 'session.version'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', fn () => view('admin.dashboard'))->name('dashboard');

    Route::middleware('role:super_admin')->group(function () {
        if (app()->environment('testing')) {
            Route::get('_role-probe', fn () => 'ok');
        }
    });
});
