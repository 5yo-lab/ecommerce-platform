<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $statusCounts = Order::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('dashboard', [
            'metrics' => [
                'revenue_today' => (float) Order::revenue()->whereDate('created_at', today())->sum('total'),
                'revenue_month' => (float) Order::revenue()->where('created_at', '>=', now()->startOfMonth())->sum('total'),
                'orders_month' => Order::where('created_at', '>=', now()->startOfMonth())->count(),
                'customers' => User::where('role', User::ROLE_CUSTOMER)->count(),
                'products' => Product::count(),
            ],
            'statusCounts' => collect(Order::STATUSES)->mapWithKeys(fn ($s) => [$s => (int) ($statusCounts[$s] ?? 0)]),
            'lowStock' => Inventory::query()
                ->with('product:id,name,sku')
                ->whereHas('product')
                ->whereColumn('quantity', '<=', 'low_stock_threshold')
                ->orderBy('quantity')
                ->limit(10)
                ->get(),
            'topProducts' => OrderItem::query()
                ->selectRaw('product_id, MAX(product_name) as product_name, SUM(quantity) as units, SUM(line_total) as revenue')
                ->whereHas('order', fn ($q) => $q->revenue())
                ->groupBy('product_id')
                ->orderByDesc('units')
                ->limit(5)
                ->get(),
            'recentOrders' => Order::with('user:id,name,email')->latest()->limit(5)->get(),
        ]);
    }
}
