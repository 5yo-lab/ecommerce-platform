<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $product->exists ? __('Edit product') : __('New product') }}
        </h2>
    </x-slot>

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @if ($product->exists) @method('PUT') @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $product->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="sku" value="SKU" />
                    <x-text-input id="sku" name="sku" class="mt-1 block w-full" :value="old('sku', $product->sku)" required />
                    <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="slug" value="Slug (generated from the name when empty)" />
                    <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug', $product->slug)" />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="category_id" value="Category" />
                    <x-select-input id="category_id" name="category_id" class="mt-1 block w-full" :options="$categories" :selected="old('category_id', $product->category_id)" placeholder="Select a category" required />
                    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="price" value="Price" />
                    <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('price', $product->price)" required />
                    <x-input-error :messages="$errors->get('price')" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="quantity" value="Stock" />
                        <x-text-input id="quantity" name="quantity" type="number" min="0" class="mt-1 block w-full" :value="old('quantity', $product->inventory?->quantity ?? 0)" required />
                        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="low_stock_threshold" value="Low-stock alert at" />
                        <x-text-input id="low_stock_threshold" name="low_stock_threshold" type="number" min="0" class="mt-1 block w-full" :value="old('low_stock_threshold', $product->inventory?->low_stock_threshold ?? 10)" required />
                        <x-input-error :messages="$errors->get('low_stock_threshold')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div>
                <x-input-label for="description" value="Description" />
                <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('description', $product->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="flex items-start gap-6">
                @if ($product->thumbnail_url)
                    <img src="{{ $product->thumbnail_url }}" alt="" class="h-24 w-24 rounded object-cover">
                @endif
                <div>
                    <x-input-label for="image" value="Image (JPG, PNG or WebP, max 4 MB; thumbnail is generated in the background)" />
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block text-sm text-gray-600 file:me-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2" />
                    <x-input-error :messages="$errors->get('image')" class="mt-2" />
                </div>
            </div>

            <label class="inline-flex items-center">
                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_active', $product->is_active))>
                <span class="ms-2 text-sm text-gray-700">{{ __('Active (visible in the store API)') }}</span>
            </label>

            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('Save') }}</x-primary-button>
                <a href="{{ route('products.index') }}" class="text-sm text-gray-600 hover:underline">{{ __('Cancel') }}</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
