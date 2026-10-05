<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:'.implode(',', User::ROLES)],
            'trashed' => ['nullable', 'in:only'],
        ]);

        $users = User::query()
            ->search($filters['search'] ?? null)
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when(($filters['trashed'] ?? null) === 'only', fn ($q) => $q->onlyTrashed())
            ->withCount('orders')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('users.index', compact('users', 'filters'));
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User(['role' => User::ROLE_CUSTOMER])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::create($request->userData());

        return redirect()->route('users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('users.form', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->userData());

        return redirect()->route('users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account here.');
        }

        $user->delete();

        return back()->with('status', "User {$user->email} moved to trash.");
    }

    public function restore(int $id): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return back()->with('status', "User {$user->email} restored.");
    }
}
