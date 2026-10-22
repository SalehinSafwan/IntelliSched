<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Allow the request only for users with one of the given roles.
     * Roles are stored in mixed case (admin, TEACHER), so compare
     * case-insensitively. Usage: ->middleware('role:admin,coordinator')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = strtolower((string) $request->user()?->role);

        abort_unless(
            in_array($role, array_map('strtolower', $roles), true),
            403,
            'You do not have permission to open this page.'
        );

        return $next($request);
    }
}
