<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowedRoles = $roles ?: ['super_admin', 'admin'];

        $user = $request->user();

        abort_unless(
            $user && ($user->hasAnyRole($allowedRoles) || in_array($user->role, $allowedRoles, true)),
            403,
        );

        return $next($request);
    }
}
