<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('media/{token}/{rev}/{file}', \App\Http\Controllers\MediaController::class)
    ->where(['token' => '[A-Za-z0-9]{32}', 'rev' => '[0-9]+', 'file' => '(master|400|800|1600)\.(jpg|webp)|file\.(pdf|mp4)'])
    ->name('media.show');

Route::get('pages/{token}', \App\Http\Controllers\SandboxedPageController::class)
    ->where('token', '[A-Za-z0-9]{32}')
    ->name('pages.sandboxed');
