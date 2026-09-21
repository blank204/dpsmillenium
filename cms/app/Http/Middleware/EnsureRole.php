<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $account = $request->user();
        abort_if(! $account || ($role === 'super_admin' && ! $account->isSuperAdmin()), 403);

        return $next($request);
    }
}
