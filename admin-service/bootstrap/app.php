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

        $middleware->encryptCookies(except: ['access_token']);

        $middleware->web(append: [
            JwtSessionMiddleware::class,
        ]);
        $middleware->prependToPriorityList(AuthenticatesRequests::class, JwtSessionMiddleware::class);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('admin/api/*') || $request->expectsJson(),
        );
    })->create();
