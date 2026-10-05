<li>
    <div class="flex items-center justify-between rounded-md px-3 py-2 hover:bg-gray-50" style="padding-left: {{ 0.75 + $depth * 1.5 }}rem">
        <div>
            <span class="font-medium text-gray-900">{{ $category->name }}</span>
            <span class="ms-2 text-gray-400">/{{ $category->slug }}</span>
            <span class="ms-2 text-xs text-gray-500">{{ $category->products_count }} {{ Str::plural('product', $category->products_count) }}</span>
        </div>
        <div class="space-x-3 whitespace-nowrap">
            <a href="{{ route('categories.create', ['parent_id' => $category->id]) }}" class="text-gray-600 hover:underline">{{ __('Add child') }}</a>
            <a href="{{ route('categories.edit', $category) }}" class="text-indigo-600 hover:underline">{{ __('Edit') }}</a>
            <form method="POST" action="{{ route('categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Move this category to trash?')">
                @csrf @method('DELETE')
                <button class="text-red-600 hover:underline">{{ __('Delete') }}</button>
            </form>
        </div>
    </div>
    @if ($category->childrenRecursive->isNotEmpty())
        <ul class="space-y-1">
            @foreach ($category->childrenRecursive as $child)
                @include('categories._node', ['category' => $child, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
