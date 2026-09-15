<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public media and custom pages live in routes/public.php (stateless, no session/cookies).
