<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Throwable;

class JwtService
{
    public function __construct(
        private readonly string $secret,
        private readonly string $issuer,
        private readonly string $cookieName,
        private readonly bool $secureCookie,
    ) {}

    /**
     * @return object{sub: string, email: string, name: string, role: string, exp: int}|null
     */
    public function decode(?string $token): ?object
    {
        if (! $token) {
            return null;
        }

        try {
            $claims = JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (Throwable) {
            return null;
        }

        if (($claims->iss ?? null) !== $this->issuer || ! isset($claims->sub, $claims->role)) {
            return null;
        }

        return $claims;
    }

    public function tokenFromRequest(Request $request): ?string
    {
        return $request->bearerToken() ?: $request->cookie($this->cookieName);
    }

    public function makeCookie(string $token, int $ttlSeconds): Cookie
    {
        return Cookie::create($this->cookieName)
            ->withValue($token)
            ->withExpires(time() + $ttlSeconds)
            ->withPath('/')
            ->withHttpOnly(true)
            ->withSecure($this->secureCookie)
            ->withSameSite(Cookie::SAMESITE_LAX)
            ->withRaw(true);
    }

    public function forgetCookie(): Cookie
    {
        return Cookie::create($this->cookieName)
            ->withValue('')
            ->withExpires(1)
            ->withPath('/')
            ->withHttpOnly(true)
            ->withSecure($this->secureCookie)
            ->withSameSite(Cookie::SAMESITE_LAX);
    }

    public function cookieName(): string
    {
        return $this->cookieName;
    }
}
