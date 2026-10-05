<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower((string) $this->input('email'))]);
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($user?->id),
            ],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $user = $this->route('user');
                if ($user && $user->is($this->user()) && $this->input('role') !== User::ROLE_ADMIN) {
                    $validator->errors()->add('role', 'You cannot remove your own admin role.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function userData(): array
    {
        $data = $this->safe()->only(['name', 'email', 'role']);
        if ($this->filled('password')) {
            $data['password'] = $this->input('password');
        }

        return $data;
    }
}
