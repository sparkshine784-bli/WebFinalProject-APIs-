<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Return a standardised success JSON response.
     *
     * @param  mixed   $data
     * @param  string  $message
     * @param  int     $status
     * @return JsonResponse
     */
    public static function success(mixed $data, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    /**
     * Return a standardised error JSON response.
     * The `errors` key is omitted when the array is empty.
     *
     * @param  string  $message
     * @param  int     $status
     * @param  array   $errors
     * @return JsonResponse
     */
    public static function error(string $message, int $status, array $errors = []): JsonResponse
    {
        $body = [
            'status'  => 'error',
            'message' => $message,
        ];

        if (!empty($errors)) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }

    /**
     * Return a standardised 201 Created JSON response.
     *
     * @param  mixed   $data
     * @param  string  $message
     * @return JsonResponse
     */
    public static function created(mixed $data, string $message = ''): JsonResponse
    {
        return self::success($data, $message, 201);
    }
}
