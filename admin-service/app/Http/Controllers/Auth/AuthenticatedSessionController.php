<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ApiGatewayClient;
use App\Services\Auth\JwtService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, ApiGatewayClient $gateway, JwtService $jwt): RedirectResponse
    {
        $request->authenticate($gateway);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false))
            ->withCookie($jwt->makeCookie($request->accessToken, $request->expiresIn));
    }

    public function destroy(Request $request, JwtService $jwt): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/')->withCookie($jwt->forgetCookie());
    }
}
