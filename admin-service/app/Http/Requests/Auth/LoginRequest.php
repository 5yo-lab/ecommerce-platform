<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\ApiGatewayClient;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public ?string $accessToken = null;

    public int $expiresIn = 0;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(ApiGatewayClient $gateway): void
    {
        $this->ensureIsNotRateLimited();

        try {
            $result = $gateway->login((string) $this->string('email'), (string) $this->string('password'));
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'email' => __('The authentication service is unavailable. Please try again shortly.'),
            ]);
        }

        if (! $result) {
            $this->failed(trans('auth.failed'));
        }

        $user = User::find($result['user']['id']);
        if (! $user?->isAdmin()) {
            $this->failed(__('This account does not have admin access.'));
        }

        Auth::login($user);
        $this->accessToken = $result['accessToken'];
        $this->expiresIn = (int) $result['expiresIn'];

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    private function failed(string $message): never
    {
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages(['email' => $message]);
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
