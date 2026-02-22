<?php

namespace ApiCore\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     *
     * @param Throwable $e
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|null
     */
    public function render(Throwable $e, Request $request): ?\Illuminate\Http\JsonResponse
    {
        // Only handle API requests
        if (!$this->isApiRequest($request)) {
            return null;
        }

        // Handle ValidationException
        if ($e instanceof ValidationException) {
            return Response::api(
                false,
                $e->getMessage() ?: 'Validation failed',
                null,
                ['errors' => $e->errors()],
                $e->status,
                $e->status
            );
        }

        // Handle ModelNotFoundException
        if ($e instanceof ModelNotFoundException || $e->getPrevious() instanceof ModelNotFoundException) {
            return Response::api(
                false,
                'Resource not found',
                null,
                [],
                404,
                404
            );
        }

        // Handle NotFoundHttpException
        if ($e instanceof NotFoundHttpException) {
            return Response::api(
                false,
                'Endpoint not found',
                null,
                [],
                404,
                404
            );
        }

        // Handle MethodNotAllowedHttpException
        if ($e instanceof MethodNotAllowedHttpException) {
            return Response::api(
                false,
                'Method not allowed',
                null,
                [],
                405,
                405
            );
        }

        // Handle AuthenticationException
        if ($e instanceof AuthenticationException) {
            return Response::api(
                false,
                $e->getMessage() ?: 'Unauthenticated',
                null,
                [],
                401,
                401
            );
        }

        // Handle AuthorizationException
        if ($e instanceof AuthorizationException) {
            return Response::api(
                false,
                $e->getMessage() ?: 'Unauthorized',
                null,
                [],
                403,
                403
            );
        }

        // Handle custom AuthException
        if ($e instanceof AuthException) {
            return Response::api(
                false,
                $e->getMessage() ?: 'Authentication failed',
                null,
                [],
                401,
                401
            );
        }

        // Handle other exceptions (including HttpExceptionInterface)
        return $this->handleGenericException($e);
    }

    /**
     * Check if the request is an API request.
     *
     * @param Request $request
     * @return bool
     */
    protected function isApiRequest(Request $request): bool
    {
        return $request->is('api/*')
            || $request->expectsJson()
            || $request->wantsJson();
    }

    /**
     * Handle generic exceptions.
     *
     * @param Throwable $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleGenericException(Throwable $e): \Illuminate\Http\JsonResponse
    {
        $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
        $message = $e->getMessage() ?: 'Internal server error';

        // In production, don't expose detailed error messages
        if (!app()->environment('local', 'development')) {
            if ($statusCode === 500) {
                $message = 'Internal server error';
            }
        }

        return Response::api(
            false,
            $message,
            app()->environment('local', 'development') ? [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ] : null,
            [],
            $statusCode,
            $statusCode
        );
    }
}


