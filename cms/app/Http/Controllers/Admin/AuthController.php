<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function show()
    {
        return response()->view('admin.auth.login')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function attempt(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $key = Str::lower($data['username']).'|'.$request->ip();
        $max = config('cms.login.max_attempts');

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            throw ValidationException::withMessages([
                'username' => "Too many attempts. Try again in {$minutes} minutes.",
            ]);
        }

        if (! Auth::attempt(['username' => $data['username'], 'password' => $data['password']])) {
            RateLimiter::hit($key, config('cms.login.lockout_seconds'));
            throw ValidationException::withMessages([
                'username' => 'Username or password is incorrect.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('session_version', $request->user()->session_version);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
