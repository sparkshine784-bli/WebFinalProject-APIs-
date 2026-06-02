<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Checks that the authenticated user's role is in the set of allowed roles.
     * Returns 403 if the user's role is not permitted.
     *
     * Usage: route()->middleware('role:admin,teacher')
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string   ...$roles  Comma-separated list of permitted roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = auth('api')->user();

        if (!$user || !in_array($user->role, $roles, true)) {
            return ApiResponse::error('Forbidden: insufficient permissions', 403);
        }

        return $next($request);
    }
}
