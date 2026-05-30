<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = match (true) {
                    $e instanceof \Illuminate\Validation\ValidationException => 422,
                    $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException ||
                    $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 404,
                    $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                    $e instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                    $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException => $e->getStatusCode(),
                    default => 500,
                };

                $message = match (true) {
                    $e instanceof \Illuminate\Validation\ValidationException => 'Los datos proporcionados no son válidos.',
                    $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException ||
                    $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 'Recurso no encontrado.',
                    $e instanceof \Illuminate\Auth\AuthenticationException => 'No autenticado.',
                    $e instanceof \Illuminate\Auth\Access\AuthorizationException => 'No autorizado.',
                    $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException => $e->getMessage(),
                    default => match (true) {
                        config('app.debug') => $e->getMessage(),
                        default => 'Error interno del servidor',
                    },
                };

                $errors = match (true) {
                    $e instanceof \Illuminate\Validation\ValidationException => $e->errors(),
                    default => match (true) {
                        $status === 500 && config('app.debug') => [
                            'exception' => get_class($e),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => array_slice($e->getTrace(), 0, 5)
                        ],
                        default => null,
                    },
                };

                $response = [
                    'status' => 'error',
                    'code' => $status,
                    'message' => $message,
                ];

                if ($errors !== null) {
                    $response['errors'] = $errors;
                }

                return response()->json($response, $status);
            }
        });
    })->create();
