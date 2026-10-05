<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ApiGatewayClient;
use App\Services\Auth\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JwtService::class, fn () => new JwtService(
            secret: (string) config('services.jwt.secret'),
            issuer: (string) config('services.jwt.issuer'),
            cookieName: (string) config('services.jwt.cookie'),
            secureCookie: (bool) config('services.jwt.secure_cookie'),
        ));

        $this->app->singleton(ApiGatewayClient::class, fn () => new ApiGatewayClient(
            baseUrl: (string) config('services.api_gateway.url'),
            apiKey: config('services.api_gateway.key'),
            timeout: (int) config('services.api_gateway.timeout'),
        ));
    }

    public function boot(): void
    {
        Auth::viaRequest('jwt', function (Request $request): ?User {
            $jwt = app(JwtService::class);
            $claims = $jwt->decode($jwt->tokenFromRequest($request));

            return $claims ? User::find($claims->sub) : null;
        });
    }
}
