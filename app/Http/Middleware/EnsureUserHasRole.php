<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'No tienes acceso a esta sección.');
        }

        if ($user->role === 'admin') {
            return $next($request);
        }

        if (! $roles || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'No tienes acceso a esta sección.');
    }
}
