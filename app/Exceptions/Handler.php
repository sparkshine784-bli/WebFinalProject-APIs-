<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // Default reporting behaviour (logs to Laravel log channel).
        });
    }

    /**
     * Render an exception into an HTTP response.
     * On API routes all exceptions are converted to JSON envelopes.
     *
     * @param  Request    $request
     * @param  Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($request->is('api/*')) {
            // 422 — Validation errors with field-level detail.
            if ($e instanceof ValidationException) {
                return ApiResponse::error(
                    'Validation failed',
                    422,
                    $e->errors()
                );
            }

            // 401 — Unauthenticated.
            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('Unauthenticated', 401);
            }

            // 403 — Forbidden.
            if ($e instanceof AuthorizationException) {
                return ApiResponse::error('Forbidden: insufficient permissions', 403);
            }

            // 500 — Any other unhandled exception; no stack trace exposed.
            return ApiResponse::error(
                'An unexpected error occurred. Please try again later.',
                500
            );
        }

        return parent::render($request, $e);
    }
}
