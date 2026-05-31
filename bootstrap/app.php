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
        then: function () {
            // Load health check routes
            if (file_exists(__DIR__.'/../routes/health.php')) {
                require __DIR__.'/../routes/health.php';
            }
        },
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth:web']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'broadcasting/auth',
            'log-viewer',
            'log-viewer/*',
            'log-viewer/api/*',
        ]);
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'n8n.secret' => \App\Http\Middleware\N8nSecretMiddleware::class,
            'horizon.auth' => \App\Http\Middleware\HorizonBasicAuth::class,
            'log-viewer.auth' => \App\Http\Middleware\LogViewerAuth::class,
            'api-docs.auth' => \App\Http\Middleware\AuthorizeApiDocs::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })

    ->create();
