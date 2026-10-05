<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtSessionMiddleware
{
    public function __construct(private readonly JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $claims = $this->jwt->decode($request->cookie($this->jwt->cookieName()));

        if (Auth::guard('web')->check()) {
            if (! $claims || (string) $claims->sub !== (string) Auth::guard('web')->id()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return $next($request);
        }

        if ($claims && $claims->role === User::ROLE_ADMIN) {
            $user = User::find($claims->sub);
            if ($user?->isAdmin()) {
                Auth::guard('web')->login($user);
                $request->session()->regenerate();
            }
        }

        return $next($request);
    }
}
