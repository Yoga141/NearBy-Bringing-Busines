<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict a route to the given roles: `->middleware('role:owner,admin')`.
 *
 * Runs after `auth:sanctum`, so a missing user here means the route was
 * registered without authentication - answered as 401, not a crash.
 */
class EnsureUserHasRole
{
    private const DENIED = [
        'admin' => 'Akses khusus admin.',
        'owner' => 'Fitur ini khusus pemilik UMKM.',
    ];

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_unless($user, 401, 'Silakan masuk terlebih dahulu.');

        if (! in_array($user->role, $roles, true)) {
            abort(403, $roles === ['admin'] ? self::DENIED['admin'] : self::DENIED['owner']);
        }

        return $next($request);
    }
}
