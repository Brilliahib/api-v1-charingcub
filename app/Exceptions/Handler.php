<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
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

    public function render($request, Throwable $exception)
    {
        // Handle JWT Token expired exception
        if ($exception instanceof TokenExpiredException) {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Token has expired',
            ], 401);
        }

        // Handle JWT Token invalid exception
        if ($exception instanceof TokenInvalidException) {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Token is invalid',
            ], 401);
        }

        // Handle JWT Exception (like no token provided)
        if ($exception instanceof JWTException) {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Token is required',
            ], 401);
        }
        
        // Handle 404 Not Found errors
        if ($exception instanceof NotFoundHttpException || $exception instanceof ModelNotFoundException) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Resource not found',
            ], 404);
        }

        // Handle 500 Internal Server Error
        if ($exception instanceof HttpException && $exception->getStatusCode() === 500) {
            return response()->json([
                'statusCode' => 500,
                'message' => 'Internal Server Error',
            ], 500);
        }

        // Handle 403 Forbidden errors
        if ($exception instanceof HttpException && $exception->getStatusCode() === 403) {
            return response()->json([
                'statusCode' => 403,
                'message' => 'Forbidden',
            ], 403);
        }

        // Handle 401 Unauthorized errors
        if ($exception instanceof HttpException && $exception->getStatusCode() === 401) {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Handle any other exceptions (default to 500)
        return response()->json([
            'statusCode' => 500,
            'message' => 'An unexpected error occurred',
        ], 500);
    }
}
