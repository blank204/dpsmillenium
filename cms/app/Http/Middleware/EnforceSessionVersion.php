<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $account = $request->user();

        // Laravel's SessionGuard caches the resolved user for the lifetime of
        // the guard instance, so a session_version bumped by another request
        // (e.g. an admin revoking sessions) would not be visible on this
        // cached copy without an explicit reload from the database.
        $account?->refresh();

        if ($account && (int) $request->session()->get('session_version') !== (int) $account->session_version) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
