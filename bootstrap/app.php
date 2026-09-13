<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'client' => \App\Http\Middleware\EnsureUserIsClient::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->has('debug')) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'class' => get_class($e),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                    'trace' => collect($e->getTrace())->map(fn($t) => ($t['file'] ?? '') . ':' . ($t['line'] ?? '') . ' ' . ($t['function'] ?? ''))->take(15),
                ], 500);
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
