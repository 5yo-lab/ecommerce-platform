<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:'.implode(',', User::ROLES)],
        ]);

        return UserResource::collection(
            User::search($filters['search'] ?? null)
                ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
                ->withCount('orders')
                ->latest()
                ->paginate(20)
                ->withQueryString()
        );
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
