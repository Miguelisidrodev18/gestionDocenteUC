<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
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

        $userRole = $user->role instanceof Role ? $user->role : Role::tryFrom((string) $user->role);

        if (! $userRole) {
            abort(403, 'Tu usuario no tiene un rol válido.');
        }

        // Admin siempre pasa
        if ($userRole === Role::ADMIN) {
            return $next($request);
        }

        if (! $roles) {
            return $next($request);
        }

        $allowed = collect($roles)
            ->map(fn (string $r) => Role::tryFrom($r))
            ->filter()
            ->contains($userRole);

        if (! $allowed) {
            abort(403, 'No tienes acceso a esta sección.');
        }

        return $next($request);
    }
}

