<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Property 5: Expired or malformed tokens are rejected
 *
 * For any string that is either expired, has an invalid signature, or is not
 * a well-formed JWT, presenting it as a Bearer token to any protected route
 * should return HTTP 401.
 *
 * Validates: Requirements 1.6
 */
class ExpiredMalformedTokenPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Protected routes to test against.
     * Each entry: [method, uri]
     */
    private const PROTECTED_ROUTES = [
        ['GET',  '/api/auth/me'],
        ['POST', '/api/auth/logout'],
        ['POST', '/api/auth/refresh'],
    ];

    /**
     * A set of malformed / structurally invalid token strings.
     * None of these are valid JWTs signed with the application secret.
     */
    private function malformedTokens(): array
    {
        return [
            // Random strings
            'not-a-jwt',
            'abc123',
            '',
            'Bearer token',
            // Looks like a JWT but has wrong number of segments
            'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIn0',
            // Three segments but garbage payload
            'eyJhbGciOiJIUzI1NiJ9.GARBAGE_PAYLOAD.GARBAGE_SIG',
            // Valid header/payload structure but wrong signature
            'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9'
                . '.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ'
                . '.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
            // Numeric string
            '1234567890',
            // SQL injection attempt
            "' OR '1'='1",
            // Very long random string
            str_repeat('x', 512),
        ];
    }

    /**
     * Build a JWT-like token with a past expiry by manipulating the payload.
     * The signature will be invalid, so the middleware should reject it as
     * TokenInvalidException (which maps to 401).
     */
    private function expiredLikeToken(): string
    {
        $header  = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'sub' => '1',
            'iat' => time() - 7200,
            'exp' => time() - 3600, // expired 1 hour ago
            'jti' => bin2hex(random_bytes(8)),
        ]));
        $sig = base64_encode('invalid_signature');

        return rtrim($header, '=') . '.' . rtrim($payload, '=') . '.' . rtrim($sig, '=');
    }

    /**
     * @test
     *
     * Property 5: Malformed tokens are rejected with 401
     * Validates: Requirements 1.6
     */
    public function malformed_tokens_are_rejected_with_401(): void
    {
        // Feature: jwt-api-auth-integration, Property 5: Expired or malformed tokens are rejected

        $this->withoutMiddleware(ThrottleRequests::class);

        $tokens    = $this->malformedTokens();
        $routes    = self::PROTECTED_ROUTES;
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Cycle through malformed tokens and routes
            $token = $tokens[$i % count($tokens)];
            [$method, $uri] = $routes[$i % count($routes)];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->json($method, $uri);

            $statusCode = $response->getStatusCode();

            $this->assertSame(
                401,
                $statusCode,
                "Iteration {$i}: Malformed token must be rejected with 401 on {$method} {$uri}. "
                . "Got {$statusCode}. Token: " . substr($token, 0, 40)
            );

            $body = $response->json();

            $this->assertIsArray($body,
                "Iteration {$i}: Response body must be a JSON object.");
            $this->assertArrayHasKey('status', $body,
                "Iteration {$i}: Response must contain 'status' key.");
            $this->assertSame('error', $body['status'],
                "Iteration {$i}: Response status must be 'error'.");
            $this->assertArrayHasKey('message', $body,
                "Iteration {$i}: Response must contain 'message' key.");
            $this->assertNotEmpty($body['message'],
                "Iteration {$i}: Error message must not be empty.");
        }
    }

    /**
     * @test
     *
     * Property 5: Expired-like tokens (invalid signature) are rejected with 401
     * Validates: Requirements 1.6
     */
    public function expired_like_tokens_are_rejected_with_401(): void
    {
        // Feature: jwt-api-auth-integration, Property 5: Expired or malformed tokens are rejected

        $this->withoutMiddleware(ThrottleRequests::class);

        $routes     = self::PROTECTED_ROUTES;
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            [$method, $uri] = $routes[$i % count($routes)];

            // Each iteration generates a fresh expired-like token
            $token = $this->expiredLikeToken();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->json($method, $uri);

            $statusCode = $response->getStatusCode();

            $this->assertSame(
                401,
                $statusCode,
                "Iteration {$i}: Expired/invalid-signature token must be rejected with 401 "
                . "on {$method} {$uri}. Got {$statusCode}."
            );

            $body = $response->json();

            $this->assertIsArray($body,
                "Iteration {$i}: Response body must be a JSON object.");
            $this->assertArrayHasKey('status', $body,
                "Iteration {$i}: Response must contain 'status' key.");
            $this->assertSame('error', $body['status'],
                "Iteration {$i}: Response status must be 'error'.");
        }
    }

    /**
     * @test
     *
     * Property 5: Requests with no Authorization header are rejected with 401
     * Validates: Requirements 1.6, 3.5
     */
    public function missing_token_is_rejected_with_401(): void
    {
        // Feature: jwt-api-auth-integration, Property 5: Expired or malformed tokens are rejected

        $this->withoutMiddleware(ThrottleRequests::class);

        $routes     = self::PROTECTED_ROUTES;
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            [$method, $uri] = $routes[$i % count($routes)];

            // No Authorization header at all
            $response = $this->json($method, $uri);

            $statusCode = $response->getStatusCode();

            $this->assertSame(
                401,
                $statusCode,
                "Iteration {$i}: Request with no token must be rejected with 401 "
                . "on {$method} {$uri}. Got {$statusCode}."
            );

            $body = $response->json();

            $this->assertIsArray($body,
                "Iteration {$i}: Response body must be a JSON object.");
            $this->assertArrayHasKey('status', $body,
                "Iteration {$i}: Response must contain 'status' key.");
            $this->assertSame('error', $body['status'],
                "Iteration {$i}: Response status must be 'error'.");
        }
    }
}
