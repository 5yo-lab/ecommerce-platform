<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStatusService
{
    public function transition(Order $order, string $status): Order
    {
        return DB::transaction(function () use ($order, $status) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $order->canTransitionTo($status)) {
                throw ValidationException::withMessages([
                    'status' => "An order that is {$order->status} cannot be changed to {$status}.",
                ]);
            }

            if ($status === 'cancelled') {
                $this->restock($order);
                if ($order->payment_status === 'paid') {
                    $order->payment_status = 'refunded';
                }
            }

            if ($status === 'paid') {
                $order->payment_status = 'paid';
            }

            $order->status = $status;
            $order->save();

            return $order;
        });
    }

    private function restock(Order $order): void
    {
        $items = $order->items()->get();
        $inventories = Inventory::whereIn('product_id', $items->pluck('product_id'))
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        foreach ($items as $item) {
            $inventory = $inventories->get($item->product_id);
            if ($inventory) {
                $inventory->quantity += $item->quantity;
                $inventory->save();
            }
        }
    }
}
