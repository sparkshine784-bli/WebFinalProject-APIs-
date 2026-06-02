<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Property 2: Invalid credentials always return 401
 *
 * For any email/password pair that does not match a registered user, the login
 * endpoint should return HTTP 401 with an error envelope (no token in the body).
 *
 * Validates: Requirements 1.2
 */
class InvalidCredentialsPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generate a random valid-looking email that is guaranteed not to exist
     * in the (empty) database.
     */
    private function randomEmail(int $seed): string
    {
        $domains = ['example.com', 'test.net', 'mail.org', 'demo.io', 'sample.dev'];
        $local   = 'user' . abs($seed % 100000);
        $domain  = $domains[abs($seed) % count($domains)];

        return $local . '@' . $domain;
    }

    /**
     * Generate a random password string that will never match any stored hash.
     */
    private function randomPassword(int $seed): string
    {
        return 'wrong_pass_' . abs($seed);
    }

    /**
     * @test
     *
     * Property 2: Invalid credentials always return 401
     * Validates: Requirements 1.2
     */
    public function invalid_credentials_always_return_401(): void
    {
        // Feature: jwt-api-auth-integration, Property 2: Invalid credentials always return 401

        $this->withoutMiddleware(ThrottleRequests::class);

        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $seed     = mt_rand(PHP_INT_MIN, PHP_INT_MAX);
            $email    = $this->randomEmail($seed);
            $password = $this->randomPassword($seed);

            $response = $this->postJson('/api/auth/login', [
                'email'    => $email,
                'password' => $password,
            ]);

            // Assert HTTP 401 is returned.
            $response->assertStatus(401);

            $body = $response->json();

            // Assert the error envelope is present.
            $this->assertArrayHasKey('status', $body,
                "Iteration {$i}: Response must contain a 'status' key. Email: {$email}");
            $this->assertSame('error', $body['status'],
                "Iteration {$i}: Response status must be 'error'. Email: {$email}");

            $this->assertArrayHasKey('message', $body,
                "Iteration {$i}: Response must contain a 'message' key. Email: {$email}");
            $this->assertNotEmpty($body['message'],
                "Iteration {$i}: Error message must not be empty. Email: {$email}");

            // Assert no token is present anywhere in the response body.
            $this->assertArrayNotHasKey('token', $body,
                "Iteration {$i}: Response must not contain a top-level 'token' key. Email: {$email}");

            if (isset($body['data']) && is_array($body['data'])) {
                $this->assertArrayNotHasKey('token', $body['data'],
                    "Iteration {$i}: Response data must not contain a 'token' key. Email: {$email}");
            }
        }
    }
}
