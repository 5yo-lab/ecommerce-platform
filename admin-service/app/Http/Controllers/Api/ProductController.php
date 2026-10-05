<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,inactive'],
            'stock' => ['nullable', 'in:low,out'],
            'trashed' => ['nullable', 'in:only'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return ProductResource::collection(
            Product::with(['category', 'inventory'])
                ->filter($filters)
                ->latest()
                ->paginate($filters['per_page'] ?? 20)
                ->withQueryString()
        );
    }

    public function show(int $id): ProductResource
    {
        return new ProductResource(Product::withTrashed()->with(['category', 'inventory'])->findOrFail($id));
    }
}
