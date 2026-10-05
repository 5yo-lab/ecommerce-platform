<?php

namespace Tests;

use Firebase\JWT\JWT;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * users/orders/order_items are owned by the NestJS migrations; the suite recreates them from tests/database/migrations.
     */
    protected function refreshApplication()
    {
        parent::refreshApplication();

        $this->app->make('migrator')->path(base_path('tests/database/migrations'));
    }

    /**
     * Sessions are bound to the shared JWT cookie, so an authenticated test request also needs a valid token.
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        $this->withUnencryptedCookie(config('services.jwt.cookie'), $this->jwtFor($user));

        return parent::actingAs($user, $guard);
    }

    protected function jwtFor(Authenticatable $user): string
    {
        return JWT::encode([
            'sub' => (string) $user->getAuthIdentifier(),
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'iss' => config('services.jwt.issuer'),
            'iat' => time(),
            'exp' => time() + 3600,
        ], config('services.jwt.secret'), 'HS256');
    }
}
