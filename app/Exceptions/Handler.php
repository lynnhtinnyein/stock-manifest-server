<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     */
    protected $levels = [];

    /**
     * A list of the exception types that are not reported.
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'new_password',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): JsonResponse
    {
        // Always return JSON for API-only application
        return $this->handleApiException($request, $e);
    }

    /**
     * Prepare exception for rendering.
     */
    protected function prepareException(Throwable $e): Throwable
    {
        // Return the exception as-is to handle in our custom handler
        return $e;
    }

    /**
     * Handle API exceptions with consistent format
     */
    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        // Validation Exception
        if ($e instanceof ValidationException) {
            Log::warning('Validation failed', [
                'errors' => $e->errors(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Validation failed. Please check your input.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Authentication Exception
        if ($e instanceof AuthenticationException) {
            // Log::warning('Authentication failed', [
            //     'url' => $request->fullUrl(),
            //     'ip' => $request->ip(),
            // ]);

            return response()->json([
                'status' => false,
                'message' => 'You are not authenticated. Please log in.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Authorization Exception
        if ($e instanceof AuthorizationException) {
            Log::warning('Authorization failed', [
                'user_id' => $request->user()?->id,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to perform this action.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Model Not Found Exception - THIS IS THE KEY ONE FOR YOUR ISSUE
        if ($e instanceof ModelNotFoundException) {
            $modelName = class_basename($e->getModel());

            // Extract ID from the exception message if available
            preg_match('/\[.*\]\s+(\d+)/', $e->getMessage(), $matches);
            $id = $matches[1] ?? 'specified';

            Log::info('Resource not found', [
                'model' => $modelName,
                'id' => $id,
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'status' => false,
                'message' => "{$modelName} with id {$id} is not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        // Not Found Exception (404 routes)
        if ($e instanceof NotFoundHttpException) {
            // Check if it's a model not found wrapped in NotFoundHttpException
            $previous = $e->getPrevious();
            if ($previous instanceof ModelNotFoundException) {
                $modelName = class_basename($previous->getModel());
                preg_match('/\[.*\]\s+(\d+)/', $previous->getMessage(), $matches);
                $id = $matches[1] ?? 'specified';

                Log::info('Resource not found', [
                    'model' => $modelName,
                    'id' => $id,
                    'url' => $request->fullUrl(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => "{$modelName} with id {$id} is not found.",
                ], Response::HTTP_NOT_FOUND);
            }

            Log::info('Route not found', [
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'The requested resource was not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Method Not Allowed Exception
        if ($e instanceof MethodNotAllowedHttpException) {
            Log::warning('Method not allowed', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'This HTTP method is not allowed for this route.',
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        // HTTP Exception
        if ($e instanceof HttpException) {
            Log::warning('HTTP exception occurred', [
                'status_code' => $e->getStatusCode(),
                'message' => $e->getMessage(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'An error occurred.',
            ], $e->getStatusCode());
        }

        // Log all other errors
        Log::error('Unhandled exception', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
        ]);

        // Generic error response (never expose internal errors to users)
        return response()->json([
            'status' => false,
            'message' => 'An unexpected error occurred. Please try again later.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'status' => false,
            'message' => 'You are not authenticated. Please log in.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
