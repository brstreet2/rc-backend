<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $exception->errors(),
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'The given data was invalid.',
                        'details' => $exception->errors(),
                    ],
                ], 422);
            }

            if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
                return response()->json([
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Resource not found.',
                    ],
                ], 404);
            }

            if ($exception instanceof QueryException) {
                return response()->json([
                    'error' => [
                        'code' => 'DATABASE_ERROR',
                        'message' => 'A database error occurred.',
                    ],
                ], 500);
            }

            return response()->json([
                'error' => [
                    'code' => 'INTERNAL_SERVER_ERROR',
                    'message' => 'Unexpected server error.',
                ],
            ], 500);
        });
    })->create();
