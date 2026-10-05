<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Categories') }}</h2>
            <div class="flex items-center gap-4">
                @if ($showTrashed)
                    <a href="{{ route('categories.index') }}" class="text-sm text-gray-600 hover:underline">{{ __('Back to tree') }}</a>
                @else
                    <a href="{{ route('categories.index', ['trashed' => 'only']) }}" class="text-sm text-gray-600 hover:underline">{{ __('Trash') }}</a>
                @endif
                <a href="{{ route('categories.create') }}"><x-primary-button type="button">{{ __('New category') }}</x-primary-button></a>
            </div>
        </div>
    </x-slot>

    <x-card>
        @if ($showTrashed)
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-500"><tr><th class="py-2">Name</th><th class="py-2">Slug</th><th class="py-2">Parent</th><th class="py-2">Deleted</th><th></th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($trashed as $category)
                        <tr>
                            <td class="py-2 font-medium">{{ $category->name }}</td>
                            <td class="py-2 text-gray-500">{{ $category->slug }}</td>
                            <td class="py-2">{{ $category->parent?->name ?? '—' }}</td>
                            <td class="py-2 text-gray-500">{{ $category->deleted_at->diffForHumans() }}</td>
                            <td class="py-2 text-right">
                                <form method="POST" action="{{ route('categories.restore', $category->id) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-indigo-600 hover:underline">{{ __('Restore') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-gray-500">Trash is empty.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @else
            @if ($tree->isEmpty())
                <p class="text-sm text-gray-500">No categories yet.</p>
            @else
                <ul class="space-y-1 text-sm">
                    @foreach ($tree as $category)
                        @include('categories._node', ['category' => $category, 'depth' => 0])
                    @endforeach
                </ul>
            @endif
        @endif
    </x-card>
</x-app-layout>
