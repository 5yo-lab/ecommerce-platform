<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Orders') }}</h2>
    </x-slot>

    <x-card>
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <x-input-label for="customer" value="Customer / order #" />
                <x-text-input id="customer" name="customer" :value="$filters['customer'] ?? ''" placeholder="Name, email or number" class="mt-1 text-sm" />
            </div>
            <div>
                <x-input-label for="status" value="Status" />
                <x-select-input id="status" name="status" class="mt-1" :options="collect(\App\Models\Order::STATUSES)->mapWithKeys(fn ($s) => [$s => ucfirst($s)])" :selected="$filters['status'] ?? null" placeholder="Any status" />
            </div>
            <div>
                <x-input-label for="from" value="From" />
                <x-text-input id="from" name="from" type="date" :value="$filters['from'] ?? ''" class="mt-1 text-sm" />
            </div>
            <div>
                <x-input-label for="to" value="To" />
                <x-text-input id="to" name="to" type="date" :value="$filters['to'] ?? ''" class="mt-1 text-sm" />
            </div>
            <div>
                <x-input-label for="min_total" value="Min total" />
                <x-text-input id="min_total" name="min_total" type="number" step="0.01" min="0" :value="$filters['min_total'] ?? ''" class="mt-1 w-28 text-sm" />
            </div>
            <div>
                <x-input-label for="max_total" value="Max total" />
                <x-text-input id="max_total" name="max_total" type="number" step="0.01" min="0" :value="$filters['max_total'] ?? ''" class="mt-1 w-28 text-sm" />
            </div>
            <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
            <a href="{{ route('orders.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('Reset') }}</a>
        </form>
    </x-card>

    <x-card>
        <table class="min-w-full text-sm">
            <thead class="text-left text-gray-500">
                <tr><th class="py-2">Order</th><th class="py-2">Customer</th><th class="py-2 text-right">Items</th><th class="py-2 text-right">Total</th><th class="py-2">Status</th><th class="py-2">Payment</th><th class="py-2">Placed</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    <tr>
                        <td class="py-2"><a href="{{ route('orders.show', $order) }}" class="font-medium text-indigo-600 hover:underline">{{ $order->order_number }}</a></td>
                        <td class="py-2">{{ $order->user?->name }} <span class="text-gray-400">{{ $order->user?->email }}</span></td>
                        <td class="py-2 text-right">{{ $order->items_count }}</td>
                        <td class="py-2 text-right">${{ number_format($order->total, 2) }}</td>
                        <td class="py-2"><x-badge :value="$order->status" /></td>
                        <td class="py-2"><x-badge :value="$order->payment_status" /></td>
                        <td class="py-2 text-gray-500">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-4 text-gray-500">No orders match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $orders->links() }}</div>
    </x-card>
</x-app-layout>
