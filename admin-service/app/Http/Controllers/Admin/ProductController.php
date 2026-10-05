<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportProductsRequest;
use App\Http\Requests\Admin\ProductRequest;
use App\Jobs\ImportProducts;
use App\Jobs\ProcessProductImage;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,inactive'],
            'stock' => ['nullable', 'in:low,out'],
            'trashed' => ['nullable', 'in:only'],
        ]);

        return view('products.index', [
            'products' => Product::with(['category', 'inventory'])
                ->filter($filters)
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'categories' => Category::treeOptions(),
            'importSummary' => Cache::get(ImportProducts::summaryKey($request->user()->id)),
        ]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => new Product(['is_active' => true]),
            'categories' => Category::treeOptions(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $imagePath = $request->file('image')?->store('products', 'public');

        $product = DB::transaction(function () use ($request, $imagePath) {
            $product = Product::create($request->productData() + ['image_path' => $imagePath]);
            $product->inventory()->create($request->inventoryData());

            return $product;
        });

        if ($imagePath) {
            ProcessProductImage::dispatch($product->id, $imagePath);
        }

        return redirect()->route('products.index')->with('status', "Product {$product->sku} created.");
    }

    public function edit(Product $product): View
    {
        return view('products.form', [
            'product' => $product->load('inventory'),
            'categories' => Category::treeOptions(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $newImage = $request->file('image')?->store('products', 'public');
        $oldFiles = array_filter([$product->image_path, $product->thumbnail_path]);

        DB::transaction(function () use ($request, $product, $newImage) {
            $data = $request->productData();
            if ($newImage) {
                $data += ['image_path' => $newImage, 'thumbnail_path' => null];
            }
            $product->update($data);
            $product->inventory()->updateOrCreate(['product_id' => $product->id], $request->inventoryData());
        });

        if ($newImage) {
            Storage::disk('public')->delete($oldFiles);
            ProcessProductImage::dispatch($product->id, $newImage);
        }

        return redirect()->route('products.index')->with('status', "Product {$product->sku} updated.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('status', "Product {$product->sku} moved to trash.");
    }

    public function restore(int $id): RedirectResponse
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return back()->with('status', "Product {$product->sku} restored.");
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ImportProducts::COLUMNS);

            Product::with(['category', 'inventory'])->orderBy('id')->chunk(500, function ($products) use ($out) {
                foreach ($products as $product) {
                    fputcsv($out, [
                        $product->sku,
                        $product->name,
                        $product->category?->slug,
                        $product->price,
                        $product->inventory?->quantity ?? 0,
                        $product->inventory?->low_stock_threshold ?? 10,
                        $product->is_active ? 1 : 0,
                        $product->description,
                    ]);
                }
            });

            fclose($out);
        }, 'products-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(ImportProductsRequest $request): RedirectResponse
    {
        $path = $request->file('file')->store('imports', 'local');
        Cache::forget(ImportProducts::summaryKey($request->user()->id));

        ImportProducts::dispatch($path, $request->user()->id);

        return back()->with('status', 'Import queued. Refresh this page in a moment to see the summary.');
    }
}
