<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Dashboard') }}</h2>
    </x-slot>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            'Revenue today' => '$'.number_format($metrics['revenue_today'], 2),
            'Revenue this month' => '$'.number_format($metrics['revenue_month'], 2),
            'Orders this month' => number_format($metrics['orders_month']),
            'Customers' => number_format($metrics['customers']),
            'Products' => number_format($metrics['products']),
        ] as $label => $value)
            <div class="bg-white shadow-sm sm:rounded-lg p-5">
                <div class="text-sm text-gray-500">{{ $label }}</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <x-card title="Orders by status">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-6">
            @foreach ($statusCounts as $status => $count)
                <a href="{{ route('orders.index', ['status' => $status]) }}" class="rounded-md border border-gray-100 p-3 hover:bg-gray-50">
                    <x-badge :value="$status" />
                    <div class="mt-2 text-xl font-semibold">{{ $count }}</div>
                </a>
            @endforeach
        </div>
    </x-card>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card title="Top products (units sold)">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-500"><tr><th class="py-1">Product</th><th class="py-1 text-right">Units</th><th class="py-1 text-right">Revenue</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($topProducts as $row)
                        <tr><td class="py-2">{{ $row->product_name }}</td><td class="py-2 text-right">{{ $row->units }}</td><td class="py-2 text-right">${{ number_format($row->revenue, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-2 text-gray-500">No sales yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        <x-card title="Low stock">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-500"><tr><th class="py-1">Product</th><th class="py-1">SKU</th><th class="py-1 text-right">Stock</th><th class="py-1 text-right">Threshold</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($lowStock as $inventory)
                        <tr>
                            <td class="py-2"><a class="text-indigo-600 hover:underline" href="{{ route('products.edit', $inventory->product_id) }}">{{ $inventory->product->name }}</a></td>
                            <td class="py-2 text-gray-500">{{ $inventory->product->sku }}</td>
                            <td class="py-2 text-right {{ $inventory->quantity === 0 ? 'text-red-600 font-semibold' : 'text-yellow-700' }}">{{ $inventory->quantity }}</td>
                            <td class="py-2 text-right text-gray-500">{{ $inventory->low_stock_threshold }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-2 text-gray-500">All products are well stocked.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>

    <x-card title="Recent orders">
        <table class="min-w-full text-sm">
            <thead class="text-left text-gray-500"><tr><th class="py-1">Order</th><th class="py-1">Customer</th><th class="py-1">Status</th><th class="py-1 text-right">Total</th><th class="py-1 text-right">Placed</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($recentOrders as $order)
                    <tr>
                        <td class="py-2"><a class="text-indigo-600 hover:underline" href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a></td>
                        <td class="py-2">{{ $order->user?->name }}</td>
                        <td class="py-2"><x-badge :value="$order->status" /></td>
                        <td class="py-2 text-right">${{ number_format($order->total, 2) }}</td>
                        <td class="py-2 text-right text-gray-500">{{ $order->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-2 text-gray-500">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
</x-app-layout>
