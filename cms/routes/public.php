<?php

use Illuminate\Support\Facades\Route;

/*
 * Stateless public routes (the `public` middleware group, see bootstrap/app.php):
 * no session, no cookie encryption, no CSRF. These responses carry public
 * cache headers, so they must never set a cookie.
 */

Route::get('media/{token}/{rev}/{file}', \App\Http\Controllers\MediaController::class)
    ->where(['token' => '[A-Za-z0-9]{32}', 'rev' => '[0-9]+', 'file' => '(master|400|800|1600)\.(jpg|webp)|file\.(pdf|mp4)'])
    ->name('media.show');

Route::get('pages/{token}', \App\Http\Controllers\SandboxedPageController::class)
    ->where('token', '[A-Za-z0-9]{32}')
    ->name('pages.sandboxed');
