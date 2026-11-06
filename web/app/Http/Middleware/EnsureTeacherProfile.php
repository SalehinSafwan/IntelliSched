<?php

namespace App\Http\Middleware;

use App\Models\Teacher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherProfile
{
    /**
     * Teacher pages need the teaching profile linked to the account.
     * Without one, explain the problem instead of failing with a 404.
     * Other roles (e.g. CR/ACR students on the messages pages) pass.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (strtolower((string) $user?->role) === 'teacher'
            && !Teacher::where('user_id', $user->id)->exists()) {
            return response()->view('teachers.not-linked', [], 403);
        }

        return $next($request);
    }
}
