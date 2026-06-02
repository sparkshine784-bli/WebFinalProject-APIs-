<?php

namespace Tests\Feature;

use App\Helpers\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Tests\TestCase;

/**
 * Feature tests for ApiResponse envelope and the global exception handler.
 *
 * Requirements: 6.2, 6.3, 6.5
 */
class ApiResponseAndExceptionHandlerTest extends TestCase
{
    /**
     * Register temporary API routes used only during these tests.
     * Called automatically by Laravel's test bootstrapping via setUp.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // success() probe
        Route::get('/api/test/success', function () {
            return ApiResponse::success(['id' => 1, 'name' => 'Test'], 'Fetched successfully');
        });

        // error() probe — no field errors
        Route::get('/api/test/error', function () {
            return ApiResponse::error('Something went wrong', 400);
        });

        // error() probe — with field errors
        Route::get('/api/test/error-with-fields', function () {
            return ApiResponse::error('Validation failed', 422, [
                'email' => ['The email field is required.'],
            ]);
        });

        // created() probe
        Route::post('/api/test/created', function () {
            return ApiResponse::created(['id' => 42, 'name' => 'New Resource'], 'Resource created');
        });

        // Throws a generic RuntimeException → should produce 500 envelope
        Route::get('/api/test/unhandled-exception', function () {
            throw new \RuntimeException('Internal boom', 0);
        });

        // Throws AuthenticationException → should produce 401 envelope
        Route::get('/api/test/unauthenticated', function () {
            throw new AuthenticationException('Unauthenticated.');
        });

        // Throws AuthorizationException → should produce 403 envelope
        Route::get('/api/test/forbidden', function () {
            throw new AuthorizationException('This action is unauthorized.');
        });

        // Throws ValidationException → should produce 422 envelope with errors
        Route::get('/api/test/validation-error', function () {
            $validator = \Illuminate\Support\Facades\Validator::make([], [
                'name'  => 'required',
                'email' => 'required|email',
            ]);
            throw new ValidationException($validator);
        });
    }

    // -------------------------------------------------------------------------
    // ApiResponse::success()
    // -------------------------------------------------------------------------

    /** @test */
    public function success_response_contains_required_envelope_fields(): void
    {
        $response = $this->getJson('/api/test/success');

        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'application/json')
                 ->assertJsonStructure(['status', 'message', 'data'])
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'Fetched successfully',
                 ]);

        $this->assertArrayHasKey('data', $response->json());
    }

    /** @test */
    public function success_response_data_contains_expected_payload(): void
    {
        $response = $this->getJson('/api/test/success');

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => ['id' => 1, 'name' => 'Test'],
                 ]);
    }

    // -------------------------------------------------------------------------
    // ApiResponse::error()
    // -------------------------------------------------------------------------

    /** @test */
    public function error_response_contains_required_envelope_fields(): void
    {
        $response = $this->getJson('/api/test/error');

        $response->assertStatus(400)
                 ->assertHeader('Content-Type', 'application/json')
                 ->assertJsonStructure(['status', 'message'])
                 ->assertJson([
                     'status'  => 'error',
                     'message' => 'Something went wrong',
                 ]);
    }

    /** @test */
    public function error_response_without_field_errors_omits_errors_key(): void
    {
        $response = $this->getJson('/api/test/error');

        $this->assertArrayNotHasKey('errors', $response->json());
    }

    /** @test */
    public function error_response_with_field_errors_includes_errors_key(): void
    {
        $response = $this->getJson('/api/test/error-with-fields');

        $response->assertStatus(422)
                 ->assertJsonStructure(['status', 'message', 'errors'])
                 ->assertJson([
                     'status'  => 'error',
                     'message' => 'Validation failed',
                 ]);

        $this->assertArrayHasKey('email', $response->json('errors'));
    }

    // -------------------------------------------------------------------------
    // ApiResponse::created()
    // -------------------------------------------------------------------------

    /** @test */
    public function created_response_returns_201_with_required_envelope_fields(): void
    {
        $response = $this->postJson('/api/test/created');

        $response->assertStatus(201)
                 ->assertHeader('Content-Type', 'application/json')
                 ->assertJsonStructure(['status', 'message', 'data'])
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'Resource created',
                     'data'    => ['id' => 42, 'name' => 'New Resource'],
                 ]);
    }

    // -------------------------------------------------------------------------
    // Exception handler — unhandled exception → 500 (Requirement 6.5)
    // -------------------------------------------------------------------------

    /** @test */
    public function unhandled_exception_returns_500_with_generic_message(): void
    {
        $response = $this->getJson('/api/test/unhandled-exception');

        $response->assertStatus(500)
                 ->assertJson([
                     'status'  => 'error',
                     'message' => 'An unexpected error occurred. Please try again later.',
                 ]);
    }

    /** @test */
    public function unhandled_exception_response_does_not_expose_stack_trace(): void
    {
        $response = $this->getJson('/api/test/unhandled-exception');

        $body = $response->json();

        // Must not contain any trace-related keys
        $this->assertArrayNotHasKey('trace', $body);
        $this->assertArrayNotHasKey('exception', $body);
        $this->assertArrayNotHasKey('file', $body);
        $this->assertArrayNotHasKey('line', $body);

        // The raw exception message must not leak into the response
        $this->assertStringNotContainsString('Internal boom', $response->getContent());
    }

    // -------------------------------------------------------------------------
    // Exception handler — AuthenticationException → 401
    // -------------------------------------------------------------------------

    /** @test */
    public function authentication_exception_returns_401_envelope(): void
    {
        $response = $this->getJson('/api/test/unauthenticated');

        $response->assertStatus(401)
                 ->assertJson([
                     'status'  => 'error',
                     'message' => 'Unauthenticated',
                 ]);
    }

    // -------------------------------------------------------------------------
    // Exception handler — AuthorizationException → 403
    // -------------------------------------------------------------------------

    /** @test */
    public function authorization_exception_returns_403_envelope(): void
    {
        $response = $this->getJson('/api/test/forbidden');

        $response->assertStatus(403)
                 ->assertJson([
                     'status'  => 'error',
                     'message' => 'Forbidden: insufficient permissions',
                 ]);
    }

    // -------------------------------------------------------------------------
    // Exception handler — ValidationException → 422 with field errors
    // -------------------------------------------------------------------------

    /** @test */
    public function validation_exception_returns_422_with_field_errors(): void
    {
        $response = $this->getJson('/api/test/validation-error');

        $response->assertStatus(422)
                 ->assertJson([
                     'status'  => 'error',
                     'message' => 'Validation failed',
                 ])
                 ->assertJsonStructure(['status', 'message', 'errors']);

        $errors = $response->json('errors');
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    /** @test */
    public function validation_exception_response_does_not_expose_stack_trace(): void
    {
        $response = $this->getJson('/api/test/validation-error');

        $body = $response->json();

        $this->assertArrayNotHasKey('trace', $body);
        $this->assertArrayNotHasKey('exception', $body);
        $this->assertArrayNotHasKey('file', $body);
        $this->assertArrayNotHasKey('line', $body);
    }
}
