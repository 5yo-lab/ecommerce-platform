<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Order') }} {{ $order->order_number }}
            </h2>
            <a href="{{ route('orders.index') }}" class="text-sm text-gray-600 hover:underline">{{ __('Back to orders') }}</a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card title="Items" class="lg:col-span-2">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-500">
                    <tr><th class="py-2">Product</th><th class="py-2 text-right">Unit price</th><th class="py-2 text-right">Qty</th><th class="py-2 text-right">Line total</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="py-2">
                                {{ $item->product_name }}
                                @if ($item->product)
                                    <span class="text-gray-400">{{ $item->product->sku }}</span>
                                @endif
                            </td>
                            <td class="py-2 text-right">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="py-2 text-right">{{ $item->quantity }}</td>
                            <td class="py-2 text-right">${{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="font-medium">
                    <tr><td colspan="3" class="pt-3 text-right text-gray-500">Subtotal</td><td class="pt-3 text-right">${{ number_format($order->subtotal, 2) }}</td></tr>
                    <tr><td colspan="3" class="text-right">Total</td><td class="text-right">${{ number_format($order->total, 2) }}</td></tr>
                </tfoot>
            </table>
        </x-card>

        <div class="space-y-6">
            <x-card title="Summary">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd><x-badge :value="$order->status" /></dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Payment</dt><dd><x-badge :value="$order->payment_status" /></dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Payment ref.</dt><dd>{{ $order->payment_reference ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Placed</dt><dd>{{ $order->created_at->format('Y-m-d H:i') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Customer</dt><dd class="text-right">{{ $order->user?->name }}<br><span class="text-gray-400">{{ $order->user?->email }}</span></dd></div>
                </dl>
            </x-card>

            <x-card title="Update status">
                @if ($order->allowedTransitions() === [])
                    <p class="text-sm text-gray-500">This order is {{ $order->status }}; no further changes are possible.</p>
                @else
                    <form method="POST" action="{{ route('orders.status', $order) }}" class="space-y-3">
                        @csrf @method('PATCH')
                        <x-select-input name="status" class="block w-full" :options="collect($order->allowedTransitions())->mapWithKeys(fn ($s) => [$s => ucfirst($s)])" />
                        <x-input-error :messages="$errors->get('status')" />
                        <p class="text-xs text-gray-500">Cancelling returns stock to inventory and refunds paid orders.</p>
                        <x-primary-button>{{ __('Update') }}</x-primary-button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
