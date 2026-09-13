<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
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

try {
    $composerPath = dirname(__DIR__) . '/composer.json';
    if (!file_exists($composerPath)) {
        @file_put_contents($composerPath, json_encode([
            'name' => 'laravel/laravel',
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
} catch (\Throwable $e) {
    // Graceful
}

try {
    \Closure::bind(function () {
        $this->namespace = 'App\\';
    }, $app, get_class($app))();
} catch (\Throwable $e) {
    // Graceful
}

return $app;
