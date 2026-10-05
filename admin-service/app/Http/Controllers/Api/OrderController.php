<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderFilterRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(OrderFilterRequest $request): AnonymousResourceCollection
    {
        return OrderResource::collection(
            Order::with('user')
                ->withCount('items')
                ->filter($request->validated())
                ->latest()
                ->paginate(20)
                ->withQueryString()
        );
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(['user', 'items']));
    }
}
