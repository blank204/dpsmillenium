<?php

use Illuminate\Support\Facades\Route;

/*
 * Stateless public routes (the `public` middleware group, see bootstrap/app.php):
 * no session, no cookie encryption, no CSRF. These responses carry public
 * cache headers, so they must never set a cookie — which means no @csrf and no
 * session() in any view rendered from here. Search is a plain GET form.
 */

Route::get('media/{token}/{rev}/{file}', \App\Http\Controllers\MediaController::class)
    ->where(['token' => '[A-Za-z0-9]{32}', 'rev' => '[0-9]+', 'file' => '(master|400|800|1600)\.(jpg|webp)|file\.(pdf|mp4)'])
    ->name('media.show');

Route::get('pages/{token}', \App\Http\Controllers\SandboxedPageController::class)
    ->where('token', '[A-Za-z0-9]{32}')
    ->name('pages.sandboxed');

Route::get('/', \App\Http\Controllers\Site\HomeController::class)->name('home');

Route::get('calendar', \App\Http\Controllers\Site\CalendarController::class)->name('calendar');
Route::get('archive', \App\Http\Controllers\Site\ArchiveController::class)->name('archive');

// Marketing pages: no data of their own, so plain views.
Route::view('about', 'site.about')->name('about');
Route::view('campus', 'site.campus')->name('campus');
Route::view('fees', 'site.fees')->name('fees');
Route::view('partnerships', 'site.partnerships')->name('partnerships');

/*
 * Section listings and single posts. The segment list is baked into the pattern from
 * Section::pathSegment() so these never shadow /calendar, /archive or /media.
 */
$segments = implode('|', array_map(fn ($s) => $s->pathSegment(), \App\Content\Section::cases()));

Route::get('{section}', \App\Http\Controllers\Site\SectionController::class)
    ->where('section', $segments)
    ->name('section');

Route::get('{section}/{id}-{slug}', \App\Http\Controllers\Site\PostController::class)
    ->where(['section' => $segments, 'id' => '[0-9]+', 'slug' => '[A-Za-z0-9-]+'])
    ->name('post');
