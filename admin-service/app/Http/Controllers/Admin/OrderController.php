<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderFilterRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(OrderFilterRequest $request): View
    {
        $filters = $request->validated();

        return view('orders.index', [
            'orders' => Order::with('user:id,name,email')
                ->withCount('items')
                ->filter($filters)
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(Order $order): View
    {
        return view('orders.show', ['order' => $order->load(['user', 'items.product'])]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, OrderStatusService $service): RedirectResponse
    {
        $order = $service->transition($order, $request->validated('status'));

        return back()->with('status', "Order {$order->order_number} is now {$order->status}.");
    }
}
