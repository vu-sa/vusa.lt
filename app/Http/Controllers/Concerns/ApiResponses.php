<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Trait for consistent API responses across all controllers.
 *
 * Use this trait in any controller that returns JSON responses
 * to ensure a standardized response format:
 *
 * Success: { success: true, data: mixed, message?: string, meta?: array }
 * Error: { success: false, message: string, errors?: array, code?: string }
 * Paginated: { success: true, data: array, meta: { pagination: {...} } }
 */
trait ApiResponses
{
    /**
     * Return a successful JSON response.
     *
     * @param  mixed  $data  The response data
     * @param  string|null  $message  Optional success message
     * @param  array<string, mixed>  $meta  Optional metadata
     */
    protected function jsonSuccess(mixed $data = null, ?string $message = null, array $meta = [], int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($message !== null) {
            $response['message'] = $message;
        }

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    /**
     * Return an error JSON response.
     *
     * @param  string  $message  Error message
     * @param  int  $status  HTTP status code
     * @param  array<string, mixed>  $errors  Detailed errors (e.g., validation)
     * @param  string|null  $code  Application-specific error code
     */
    protected function jsonError(string $message, int $status = 400, array $errors = [], ?string $code = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        if ($code !== null) {
            $response['code'] = $code;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a paginated JSON response.
     *
     * @param  LengthAwarePaginator<mixed>  $paginator  The paginator instance
     * @param  string|null  $message  Optional success message
     * @param  array<string, mixed>  $additionalMeta  Additional metadata to merge
     */
    protected function jsonPaginated(LengthAwarePaginator $paginator, ?string $message = null, array $additionalMeta = []): JsonResponse
    {
        $meta = [
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            ...$additionalMeta,
        ];

        return $this->jsonSuccess(
            data: $paginator->items(),
            message: $message,
            meta: $meta
        );
    }

    /**
     * Return a not found error response.
     */
    protected function jsonNotFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->jsonError($message, 404, code: 'NOT_FOUND');
    }

    /**
     * Return a forbidden error response.
     */
    protected function jsonForbidden(string $message = 'Access denied'): JsonResponse
    {
        return $this->jsonError($message, 403, code: 'FORBIDDEN');
    }

    /**
     * Return an unauthorized error response.
     */
    protected function jsonUnauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->jsonError($message, 401, code: 'UNAUTHORIZED');
    }

    /**
     * Return a validation error response.
     *
     * @param  array<string, mixed>  $errors  Validation errors
     */
    protected function jsonValidationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->jsonError($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Return a created response (201).
     *
     * @param  mixed  $data  The created resource
     * @param  string|null  $message  Optional success message
     */
    protected function jsonCreated(mixed $data = null, ?string $message = null): JsonResponse
    {
        return $this->jsonSuccess($data, $message ?? 'Resource created successfully', status: 201);
    }

    /**
     * Return a no content response (204).
     */
    protected function jsonNoContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
