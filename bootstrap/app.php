<?php

use App\Http\Middleware\BackofficeAuthenticate;
use App\Http\Middleware\CheckPasswordExpiry;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PortalAuthenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'auth.backoffice'        => BackofficeAuthenticate::class,
            'auth.portal'            => PortalAuthenticate::class,
            'check.password.expiry'  => CheckPasswordExpiry::class,
            'api.key'                => \App\Http\Middleware\ApiKeyMiddleware::class,
            'tenant.scope'           => \App\Http\Middleware\TenantScopeMiddleware::class,
            'webhook.hmac'           => \App\Http\Middleware\HmacWebhookMiddleware::class,
        ]);

        $middleware->priority([
            \App\Http\Middleware\ApiKeyMiddleware::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->is('api/*'));
    })->create();
