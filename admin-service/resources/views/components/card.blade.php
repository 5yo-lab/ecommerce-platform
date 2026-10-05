@props(['title' => null])

<div {{ $attributes->merge(['class' => 'bg-white shadow-sm sm:rounded-lg']) }}>
    @if ($title)
        <div class="border-b border-gray-100 px-6 py-4 font-semibold text-gray-800">{{ $title }}</div>
    @endif
    <div class="p-6">
        {{ $slot }}
    </div>
</div>
