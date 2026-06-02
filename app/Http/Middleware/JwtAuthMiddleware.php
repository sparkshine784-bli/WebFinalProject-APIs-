<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Validates the Bearer JWT token from the Authorization header.
     * Returns 401 for missing, expired, blacklisted, or malformed tokens.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            $user = auth('api')->authenticate();

            if (!$user) {
                return ApiResponse::error('Unauthenticated', 401);
            }
        } catch (TokenExpiredException $e) {
            return ApiResponse::error('Token has expired', 401);
        } catch (TokenBlacklistedException $e) {
            return ApiResponse::error('Token has been revoked', 401);
        } catch (TokenInvalidException $e) {
            return ApiResponse::error('Token is invalid', 401);
        } catch (JWTException $e) {
            return ApiResponse::error('Unauthenticated', 401);
        }

        return $next($request);
    }
}
