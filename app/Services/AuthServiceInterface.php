<?php

namespace App\Services;

use App\Models\User;

interface AuthServiceInterface
{
    /**
     * Attempt login and return token array or throw AuthenticationException.
     *
     * @param  string  $email
     * @param  string  $password
     * @return array{token: string, expires_in: int}
     */
    public function login(string $email, string $password): array;

    /**
     * Register a new user.
     *
     * @param  array  $data
     * @return User
     */
    public function register(array $data): User;

    /**
     * Logout the current user (invalidates token into blacklist).
     *
     * @return void
     */
    public function logout(): void;

    /**
     * Refresh the current token and return a new token array.
     *
     * @return array{token: string, expires_in: int}
     */
    public function refresh(): array;

    /**
     * Return the currently authenticated user.
     *
     * @return User
     */
    public function me(): User;
}
