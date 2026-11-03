<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Restrict a route to the given roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $allowed = array_map(
            fn (string $role): string => UserRole::from($role)->value,
            $roles,
        );

        abort_unless(in_array($user->role->value, $allowed, true), 403);

        return $next($request);
    }
}
