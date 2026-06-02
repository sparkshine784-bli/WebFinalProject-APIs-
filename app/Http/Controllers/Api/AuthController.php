<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthServiceInterface;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private AuthServiceInterface $authService)
    {
    }

    /**
     * Register a new user.
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return ApiResponse::created(
            $user->only(['id', 'name', 'email', 'role']),
            'User registered successfully.'
        );
    }

    /**
     * Authenticate a user and return a JWT token.
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $tokenData = $this->authService->login(
            $request->input('email'),
            $request->input('password')
        );

        return ApiResponse::success($tokenData, 'Login successful.');
    }

    /**
     * Logout the authenticated user (blacklists the token).
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    /**
     * Refresh the current JWT token.
     * POST /api/auth/refresh
     */
    public function refresh(): JsonResponse
    {
        $tokenData = $this->authService->refresh();

        return ApiResponse::success($tokenData, 'Token refreshed successfully.');
    }

    /**
     * Return the authenticated user's profile.
     * GET /api/auth/me
     */
    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        return ApiResponse::success(
            $user->only(['id', 'name', 'email', 'role']),
            'Authenticated user retrieved.'
        );
    }
}
