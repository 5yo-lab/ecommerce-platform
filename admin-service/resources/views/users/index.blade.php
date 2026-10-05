<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Users') }}</h2>
            <a href="{{ route('users.create') }}"><x-primary-button type="button">{{ __('New user') }}</x-primary-button></a>
        </div>
    </x-slot>

    <x-card>
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <x-input-label for="search" value="Search" />
                <x-text-input id="search" name="search" :value="$filters['search'] ?? ''" placeholder="Name or email" class="mt-1 text-sm" />
            </div>
            <div>
                <x-input-label for="role" value="Role" />
                <x-select-input id="role" name="role" class="mt-1" :options="['customer' => 'Customer', 'admin' => 'Admin']" :selected="$filters['role'] ?? null" placeholder="All roles" />
            </div>
            <div>
                <x-input-label for="trashed" value="Show" />
                <x-select-input id="trashed" name="trashed" class="mt-1" :options="['only' => 'Trash']" :selected="$filters['trashed'] ?? null" placeholder="Active users" />
            </div>
            <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
            <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('Reset') }}</a>
        </form>
    </x-card>

    <x-card>
        <table class="min-w-full text-sm">
            <thead class="text-left text-gray-500">
                <tr><th class="py-2">Name</th><th class="py-2">Email</th><th class="py-2">Role</th><th class="py-2 text-right">Orders</th><th class="py-2">Created</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr>
                        <td class="py-2 font-medium">{{ $user->name }}</td>
                        <td class="py-2">{{ $user->email }}</td>
                        <td class="py-2"><x-badge :value="$user->role" /></td>
                        <td class="py-2 text-right">{{ $user->orders_count }}</td>
                        <td class="py-2 text-gray-500">{{ $user->created_at?->format('Y-m-d') }}</td>
                        <td class="py-2 text-right space-x-3 whitespace-nowrap">
                            @if ($user->trashed())
                                <form method="POST" action="{{ route('users.restore', $user->id) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="text-indigo-600 hover:underline">{{ __('Restore') }}</button>
                                </form>
                            @else
                                <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:underline">{{ __('Edit') }}</a>
                                @unless ($user->is(auth()->user()))
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('Move this user to trash?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                @endunless
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-4 text-gray-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $users->links() }}</div>
    </x-card>
</x-app-layout>
