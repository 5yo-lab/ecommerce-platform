<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGatewayLogin(User $user): void
    {
        Http::fake([
            '*/api/auth/login' => fn (Request $request) => $request['password'] === 'password'
                ? Http::response([
                    'accessToken' => $this->jwtFor($user),
                    'expiresIn' => 3600,
                    'user' => ['id' => (string) $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
                ])
                : Http::response(['statusCode' => 401, 'message' => 'Invalid credentials'], 401),
        ]);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_admins_authenticate_through_the_user_service_and_receive_the_shared_jwt_cookie(): void
    {
        $user = User::factory()->admin()->create();
        $this->fakeGatewayLogin($user);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertCookie('access_token', encrypted: false);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/api/auth/login'));
    }

    public function test_customers_can_not_access_the_admin_panel(): void
    {
        $user = User::factory()->create();
        $this->fakeGatewayLogin($user);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->admin()->create();
        $this->fakeGatewayLogin($user);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);

        $this->assertGuest();
    }

    public function test_session_ends_when_the_shared_jwt_cookie_is_gone(): void
    {
        $user = User::factory()->admin()->create();

        $this->be($user)->get('/dashboard')->assertRedirect('/login');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
        $response->assertCookieExpired('access_token');
    }
}
