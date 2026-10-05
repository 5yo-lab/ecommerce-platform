<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\JwtSessionMiddleware;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'admin/api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // The JWT cookie is shared with the NestJS API, which cannot decrypt Laravel-encrypted cookies
        $middleware->encryptCookies(except: ['access_token']);

        $middleware->web(append: [
            JwtSessionMiddleware::class,
        ]);
        // Must settle the session before `auth` decides between redirect-to-login and continuing
        $middleware->prependToPriorityList(AuthenticatesRequests::class, JwtSessionMiddleware::class);

        // Nginx is the only entry point
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('admin/api/*') || $request->expectsJson(),
        );
    })->create();
