@props(['value'])

@php
    $colors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'paid' => 'bg-blue-100 text-blue-800',
        'processing' => 'bg-indigo-100 text-indigo-800',
        'shipped' => 'bg-purple-100 text-purple-800',
        'delivered' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-gray-200 text-gray-700',
        'failed' => 'bg-red-100 text-red-800',
        'refunded' => 'bg-gray-200 text-gray-700',
        'admin' => 'bg-indigo-100 text-indigo-800',
        'customer' => 'bg-gray-100 text-gray-700',
        'active' => 'bg-green-100 text-green-800',
        'inactive' => 'bg-gray-200 text-gray-700',
        'trashed' => 'bg-red-100 text-red-800',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium '.($colors[$value] ?? 'bg-gray-100 text-gray-700')]) }}>
    {{ ucfirst($value) }}
</span>
