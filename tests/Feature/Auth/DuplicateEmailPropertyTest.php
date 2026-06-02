<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Property 8: Duplicate email registration returns 422 with field error
 *
 * For any email address already present in the users table, a registration
 * attempt using that email should return HTTP 422 with an errors.email field
 * in the response body.
 *
 * Validates: Requirements 2.2
 */
class DuplicateEmailPropertyTest extends TestCase
{
    use RefreshDatabase;

    /** Valid roles for random selection. */
    private const ROLES = ['admin', 'teacher', 'student'];

    /**
     * Generate a unique email for each iteration to avoid cross-iteration
     * collisions within a single RefreshDatabase cycle.
     */
    private function uniqueEmail(int $iteration): string
    {
        $domains = ['example.com', 'test.net', 'mail.org', 'demo.io', 'sample.dev'];
        $domain  = $domains[$iteration % count($domains)];

        return 'dup_user_' . $iteration . '_' . mt_rand(1000, 9999) . '@' . $domain;
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
     * Generate a random password of at least 8 characters.
     */
    private function randomPassword(int $seed): string
    {
        return 'Pass_' . abs($seed % 100000) . '_Abc!';
    }

    /**
     * @test
     *
     * Property 8: Duplicate email registration returns 422 with field error
     * Validates: Requirements 2.2
     */
    public function duplicate_email_registration_returns_422_with_field_error(): void
    {
        // Feature: jwt-api-auth-integration, Property 8: Duplicate email registration returns 422 with field error

        $this->withoutMiddleware(ThrottleRequests::class);

        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $seed     = mt_rand(PHP_INT_MIN, PHP_INT_MAX);
            $name     = $this->randomName($seed);
            $email    = $this->uniqueEmail($i);
            $password = $this->randomPassword($seed);
            $role     = self::ROLES[abs($seed) % count(self::ROLES)];

            // First registration — must succeed with HTTP 201.
            $firstResponse = $this->postJson('/api/auth/register', [
                'name'     => $name,
                'email'    => $email,
                'password' => $password,
                'role'     => $role,
            ]);

            $firstResponse->assertStatus(201,
                "Iteration {$i}: Initial registration failed for email: {$email}");

            // Second registration with the same email — must return HTTP 422.
            $secondName     = $this->randomName($seed + 1);
            $secondPassword = $this->randomPassword($seed + 1);
            $secondRole     = self::ROLES[abs($seed + 1) % count(self::ROLES)];

            $secondResponse = $this->postJson('/api/auth/register', [
                'name'     => $secondName,
                'email'    => $email,
                'password' => $secondPassword,
                'role'     => $secondRole,
            ]);

            // Assert HTTP 422 is returned for the duplicate email.
            $secondResponse->assertStatus(422,
                "Iteration {$i}: Expected 422 for duplicate email '{$email}', "
                . "got {$secondResponse->getStatusCode()} instead.");

            $body = $secondResponse->json();

            // Assert the error envelope is present.
            $this->assertArrayHasKey('status', $body,
                "Iteration {$i}: Response must contain a 'status' key. Email: {$email}");
            $this->assertSame('error', $body['status'],
                "Iteration {$i}: Response status must be 'error'. Email: {$email}");

            $this->assertArrayHasKey('message', $body,
                "Iteration {$i}: Response must contain a 'message' key. Email: {$email}");

            // Assert field-level errors are present.
            $this->assertArrayHasKey('errors', $body,
                "Iteration {$i}: Response must contain an 'errors' key for validation failures. Email: {$email}");

            // Assert the errors.email field is present, identifying the duplicate.
            $this->assertArrayHasKey('email', $body['errors'],
                "Iteration {$i}: Response errors must contain an 'email' key identifying the duplicate. Email: {$email}");

            $this->assertNotEmpty($body['errors']['email'],
                "Iteration {$i}: The errors.email field must not be empty. Email: {$email}");
        }
    }
}
