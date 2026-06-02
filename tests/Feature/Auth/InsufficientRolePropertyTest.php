<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Property 11: Insufficient role returns 403
 *
 * For any authenticated user whose role is not in the set of roles permitted
 * for a given endpoint, the request should return HTTP 403 with an error envelope.
 *
 * Validates: Requirements 3.2, 3.3
 */
class InsufficientRolePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Mapping of endpoints restricted to admin/teacher only.
     * A student hitting these should receive 403.
     *
     * Each entry: [method, uri, body]
     */
    private const ADMIN_TEACHER_ENDPOINTS = [
        ['POST', '/api/grades',      []],
        ['PUT',  '/api/grades/1',    []],
        ['POST', '/api/attendance',  []],
        ['PUT',  '/api/attendance/1', []],
    ];

    /**
     * Mapping of endpoints restricted to admin only.
     * A teacher hitting these should receive 403.
     * (Currently none defined — extend when admin-only routes are added.)
     *
     * Each entry: [method, uri, body]
     */
    private const ADMIN_ONLY_ENDPOINTS = [
        // No admin-only routes exist yet beyond admin+teacher shared routes.
        // This array is intentionally empty; the test below covers it when populated.
    ];

    /**
     * Generate a unique email for each iteration.
     */
    private function uniqueEmail(string $role, int $iteration): string
    {
        $domains = ['example.com', 'test.net', 'mail.org', 'demo.io', 'sample.dev'];
        $domain  = $domains[$iteration % count($domains)];

        return 'role_test_' . $role . '_' . $iteration . '_' . mt_rand(1000, 9999) . '@' . $domain;
    }

    /**
     * Generate a random name.
     */
    private function randomName(int $seed): string
    {
        $firstNames = ['Alice', 'Bob', 'Carol', 'Dave', 'Eve', 'Frank', 'Grace', 'Hank'];
        $lastNames  = ['Smith', 'Jones', 'Brown', 'Taylor', 'Wilson', 'Davis', 'Clark'];

        return $firstNames[abs($seed) % count($firstNames)]
            . ' '
            . $lastNames[abs($seed + 1) % count($lastNames)];
    }

    /**
     * Register a user with the given role and return a JWT token.
     */
    private function registerAndLogin(string $role, int $iteration): string
    {
        $seed     = mt_rand(PHP_INT_MIN, PHP_INT_MAX);
        $email    = $this->uniqueEmail($role, $iteration);
        $password = 'Pass_' . abs($seed % 100000) . '_Abc!';
        $name     = $this->randomName($seed);

        $registerResponse = $this->postJson('/api/auth/register', [
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
            'role'     => $role,
        ]);

        $registerResponse->assertStatus(201,
            "Registration failed for role={$role}, iteration={$iteration}: "
            . $registerResponse->getContent());

        $loginResponse = $this->postJson('/api/auth/login', [
            'email'    => $email,
            'password' => $password,
        ]);

        $loginResponse->assertStatus(200,
            "Login failed for role={$role}, iteration={$iteration}: "
            . $loginResponse->getContent());

        $body = $loginResponse->json();

        $this->assertArrayHasKey('data', $body,
            "Login response missing 'data' key for role={$role}, iteration={$iteration}");
        $this->assertArrayHasKey('token', $body['data'],
            "Login response data missing 'token' key for role={$role}, iteration={$iteration}");

        return $body['data']['token'];
    }

    /**
     * Assert that a response is a 403 error envelope.
     */
    private function assert403ErrorEnvelope(
        \Illuminate\Testing\TestResponse $response,
        string $context
    ): void {
        $response->assertStatus(403,
            "Expected 403 but got {$response->getStatusCode()} — {$context}");

        $body = $response->json();

        $this->assertIsArray($body,
            "Response body must be a JSON object — {$context}");

        $this->assertArrayHasKey('status', $body,
            "Response must contain 'status' key — {$context}");
        $this->assertSame('error', $body['status'],
            "Response status must be 'error' — {$context}");

        $this->assertArrayHasKey('message', $body,
            "Response must contain 'message' key — {$context}");
        $this->assertNotEmpty($body['message'],
            "Error message must not be empty — {$context}");
    }

    /**
     * @test
     *
     * Property 11: Insufficient role returns 403 (student accessing admin/teacher endpoints)
     * Validates: Requirements 3.2
     *
     * Req 3.2: WHEN an authenticated user with the `student` role requests an endpoint
     * restricted to `admin` or `teacher` roles, THE Auth_Middleware SHALL return an
     * HTTP 403 response with a descriptive error message.
     */
    public function student_accessing_admin_teacher_endpoint_returns_403(): void
    {
        // Feature: jwt-api-auth-integration, Property 11: Insufficient role returns 403

        $this->withoutMiddleware(ThrottleRequests::class);

        $endpoints  = self::ADMIN_TEACHER_ENDPOINTS;
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Obtain a token for a student user.
            $token = $this->registerAndLogin('student', $i);

            // Pick an endpoint restricted to admin/teacher (cycle through all).
            [$method, $uri, $body] = $endpoints[$i % count($endpoints)];

            $context = "Iteration {$i}: student → {$method} {$uri}";

            // Make the request with the student's token.
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->json($method, $uri, $body);

            $this->assert403ErrorEnvelope($response, $context);
        }
    }

    /**
     * @test
     *
     * Property 11: Insufficient role returns 403 (teacher accessing admin-only endpoints)
     * Validates: Requirements 3.3
     *
     * Req 3.3: WHEN an authenticated user with the `teacher` role requests an endpoint
     * restricted to `admin` role only, THE Auth_Middleware SHALL return an HTTP 403
     * response with a descriptive error message.
     *
     * Note: No admin-only routes are currently defined. This test will be a no-op
     * until admin-only routes are added to ADMIN_ONLY_ENDPOINTS.
     */
    public function teacher_accessing_admin_only_endpoint_returns_403(): void
    {
        // Feature: jwt-api-auth-integration, Property 11: Insufficient role returns 403

        $this->withoutMiddleware(ThrottleRequests::class);

        $endpoints = self::ADMIN_ONLY_ENDPOINTS;

        if (empty($endpoints)) {
            $this->markTestSkipped(
                'No admin-only endpoints are currently defined. '
                . 'Add routes to ADMIN_ONLY_ENDPOINTS when admin-only routes are implemented.'
            );
        }

        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Obtain a token for a teacher user.
            $token = $this->registerAndLogin('teacher', $i);

            // Pick an admin-only endpoint (cycle through all).
            [$method, $uri, $body] = $endpoints[$i % count($endpoints)];

            $context = "Iteration {$i}: teacher → {$method} {$uri}";

            // Make the request with the teacher's token.
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->json($method, $uri, $body);

            $this->assert403ErrorEnvelope($response, $context);
        }
    }
}
