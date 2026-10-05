<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $category->exists ? __('Edit category') : __('New category') }}
        </h2>
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" class="space-y-5">
            @csrf
            @if ($category->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $category->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="slug" value="Slug (generated from the name when empty)" />
                <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug', $category->slug)" />
                <x-input-error :messages="$errors->get('slug')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="parent_id" value="Parent category" />
                <x-select-input id="parent_id" name="parent_id" class="mt-1 block w-full" :options="$parents" :selected="old('parent_id', $category->parent_id)" placeholder="— None (top level) —" />
                <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
            </div>

            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('Save') }}</x-primary-button>
                <a href="{{ route('categories.index') }}" class="text-sm text-gray-600 hover:underline">{{ __('Cancel') }}</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
