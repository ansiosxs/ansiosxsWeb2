<?php

use App\Http\Middleware\EnsureAccountIsConfigured;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\LogUserAction;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureUserRole::class,
            'account.configured' => EnsureAccountIsConfigured::class,
            'log.user.action' => LogUserAction::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'mensaje' => 'Los datos enviados no son válidos.',
                    'errores' => $exception->errors(),
                ], 422);
            }
        });
    })->create();

return $app;
