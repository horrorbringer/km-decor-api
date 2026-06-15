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

        abort_unless($request->user() && in_array($request->user()->role, $allowedRoles, true), 403);

        return $next($request);
    }
}
