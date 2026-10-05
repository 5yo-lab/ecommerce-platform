<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Products') }}</h2>
            <div class="flex items-center gap-4">
                <a href="{{ route('products.export') }}" class="text-sm text-gray-600 hover:underline">{{ __('Export CSV') }}</a>
                <a href="{{ route('products.create') }}"><x-primary-button type="button">{{ __('New product') }}</x-primary-button></a>
            </div>
        </div>
    </x-slot>

    @if ($importSummary)
        <div class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
            <div class="font-medium">
                Last import ({{ $importSummary['finished_at'] }}): {{ $importSummary['created'] }} created,
                {{ $importSummary['updated'] }} updated, {{ $importSummary['failed'] }} failed.
            </div>
            @if (! empty($importSummary['errors']))
                <ul class="mt-2 list-disc ps-5 text-blue-800">
                    @foreach ($importSummary['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <x-card>
        <div class="flex flex-wrap items-end justify-between gap-6">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <x-input-label for="search" value="Search" />
                    <x-text-input id="search" name="search" :value="$filters['search'] ?? ''" placeholder="Name or SKU" class="mt-1 text-sm" />
                </div>
                <div>
                    <x-input-label for="category_id" value="Category" />
                    <x-select-input id="category_id" name="category_id" class="mt-1" :options="$categories" :selected="$filters['category_id'] ?? null" placeholder="All categories" />
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <x-select-input id="status" name="status" class="mt-1" :options="['active' => 'Active', 'inactive' => 'Inactive']" :selected="$filters['status'] ?? null" placeholder="Any" />
                </div>
                <div>
                    <x-input-label for="stock" value="Stock" />
                    <x-select-input id="stock" name="stock" class="mt-1" :options="['low' => 'Low stock', 'out' => 'Out of stock']" :selected="$filters['stock'] ?? null" placeholder="Any" />
                </div>
                <div>
                    <x-input-label for="trashed" value="Show" />
                    <x-select-input id="trashed" name="trashed" class="mt-1" :options="['only' => 'Trash']" :selected="$filters['trashed'] ?? null" placeholder="Catalogue" />
                </div>
                <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
                <a href="{{ route('products.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('Reset') }}</a>
            </form>

            <form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data" class="flex items-end gap-2">
                @csrf
                <div>
                    <x-input-label for="file" value="Bulk import (CSV, same columns as export)" />
                    <input id="file" name="file" type="file" accept=".csv,text/csv" required class="mt-1 block text-sm text-gray-600 file:me-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2" />
                </div>
                <x-secondary-button type="submit">{{ __('Import') }}</x-secondary-button>
            </form>
        </div>
    </x-card>

    <x-card>
        <table class="min-w-full text-sm">
            <thead class="text-left text-gray-500">
                <tr><th class="py-2"></th><th class="py-2">Product</th><th class="py-2">SKU</th><th class="py-2">Category</th><th class="py-2 text-right">Price</th><th class="py-2 text-right">Stock</th><th class="py-2">Status</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($products as $product)
                    <tr>
                        <td class="py-2 w-14">
                            @if ($product->thumbnail_url)
                                <img src="{{ $product->thumbnail_url }}" alt="" class="h-10 w-10 rounded object-cover">
                            @else
                                <div class="h-10 w-10 rounded bg-gray-100"></div>
                            @endif
                        </td>
                        <td class="py-2 font-medium">{{ $product->name }}</td>
                        <td class="py-2 text-gray-500">{{ $product->sku }}</td>
                        <td class="py-2">{{ $product->category?->name }}</td>
                        <td class="py-2 text-right">${{ number_format($product->price, 2) }}</td>
                        <td class="py-2 text-right {{ $product->inventory?->isLow() ? 'text-red-600 font-semibold' : '' }}">{{ $product->inventory?->quantity ?? 0 }}</td>
                        <td class="py-2">
                            @if ($product->trashed())
                                <x-badge value="trashed" />
                            @else
                                <x-badge :value="$product->is_active ? 'active' : 'inactive'" />
                            @endif
                        </td>
                        <td class="py-2 text-right space-x-3 whitespace-nowrap">
                            @if ($product->trashed())
                                <form method="POST" action="{{ route('products.restore', $product->id) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="text-indigo-600 hover:underline">{{ __('Restore') }}</button>
                                </form>
                            @else
                                <a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:underline">{{ __('Edit') }}</a>
                                <form method="POST" action="{{ route('products.destroy', $product) }}" class="inline" onsubmit="return confirm('Move this product to trash?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">{{ __('Delete') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-4 text-gray-500">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $products->links() }}</div>
    </x-card>
</x-app-layout>
