<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;

class AuthService implements AuthServiceInterface
{
    /**
     * Attempt login with the given credentials.
     *
     * @param  string  $email
     * @param  string  $password
     * @return array{token: string, expires_in: int}
     *
     * @throws AuthenticationException
     */
    public function login(string $email, string $password): array
    {
        $token = auth('api')->attempt(['email' => $email, 'password' => $password]);

        if (!$token) {
            throw new AuthenticationException('Invalid credentials.');
        }

        return $this->tokenArray($token);
    }

    /**
     * Register a new user with hashed password.
     *
     * @param  array  $data  Validated data: name, email, password, role
     * @return User
     */
    public function register(array $data): User
    {
        return User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
            'role'     => $data['role'],
        ]);
    }

    /**
     * Logout the authenticated user (adds token to blacklist).
     *
     * @return void
     */
    public function logout(): void
    {
        auth('api')->logout();
    }

    /**
     * Refresh the current token and return a new token array.
     *
     * @return array{token: string, expires_in: int}
     */
    public function refresh(): array
    {
        $token = auth('api')->refresh();

        return $this->tokenArray($token);
    }

    /**
     * Return the currently authenticated user.
     *
     * @return User
     */
    public function me(): User
    {
        return auth('api')->user();
    }

    /**
     * Build the standard token response array.
     *
     * @param  string  $token
     * @return array{token: string, expires_in: int}
     */
    private function tokenArray(string $token): array
    {
        return [
            'token'      => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }
}
