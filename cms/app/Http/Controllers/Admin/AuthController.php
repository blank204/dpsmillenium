<?php

namespace App\Http\Controllers\Admin;

use App\Audit\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Support\Actor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function show()
    {
        return response()->view('admin.auth.login')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function attempt(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        // Resolve the account through the DB first so the lockout key collides
        // exactly the way the column collation does (accent-, case- and
        // width-insensitive): "ÉDITOR", "édïtör" and full-width "ｅｄｉｔｏｒ" all
        // share the one budget of the "editor" account. Every username that
        // matches no account shares a single per-IP bucket, which also bounds
        // how many audit rows an unauthenticated client can create.
        $account = Account::where('username', $data['username'])->first();
        $key = 'login:'.($account ? 'account:'.$account->id : 'unknown').'|'.$request->ip();
        $max = config('cms.login.max_attempts');

        if (RateLimiter::tooManyAttempts($key, $max)) {
            // Already locked: the lockout itself was audited when it began, so
            // blocked attempts write nothing (no unbounded audit growth).
            throw ValidationException::withMessages([
                'username' => $this->lockedMessage($key),
            ]);
        }

        if (! Auth::attempt(['username' => $data['username'], 'password' => $data['password']])) {
            RateLimiter::hit($key, config('cms.login.lockout_seconds'));
            $audit->record('auth.login_failed', Actor::fromRequest($request, null), $account, null, ['username' => $data['username']]);

            if (RateLimiter::tooManyAttempts($key, $max)) {
                $audit->record('auth.lockout', Actor::fromRequest($request, null), $account, null, [
                    'attempted_username' => $data['username'],
                    'failed_attempts' => $max,
                    'locked_seconds' => (int) config('cms.login.lockout_seconds'),
                ]);
            }

            throw ValidationException::withMessages([
                'username' => 'Username or password is incorrect.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('session_version', $request->user()->session_version);
        $audit->record('auth.login', Actor::fromRequest($request, null), $request->user());

        return redirect()->route('admin.dashboard');
    }

    private function lockedMessage(string $key): string
    {
        $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

        return "Too many attempts. Try again in {$minutes} minutes.";
    }

    public function logout(Request $request, AuditLogger $audit)
    {
        $audit->record('auth.logout', Actor::fromRequest($request, null), $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
