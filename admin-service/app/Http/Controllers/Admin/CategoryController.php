<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $showTrashed = $request->query('trashed') === 'only';

        return view('categories.index', [
            'showTrashed' => $showTrashed,
            'tree' => $showTrashed ? collect() : Category::roots()->with('childrenRecursive')->withCount('products')->orderBy('name')->get(),
            'trashed' => $showTrashed ? Category::onlyTrashed()->with('parent')->latest('deleted_at')->get() : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('categories.form', [
            'category' => new Category(['parent_id' => $request->integer('parent_id') ?: null]),
            'parents' => Category::treeOptions(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()->route('categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('categories.form', [
            'category' => $category,
            'parents' => Category::treeOptions([$category->id, ...$category->descendantIds()]),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists() || $category->products()->exists()) {
            return back()->with('error', "\"{$category->name}\" still has subcategories or products. Move or delete them first.");
        }

        $category->delete();

        return back()->with('status', "Category \"{$category->name}\" moved to trash.");
    }

    public function restore(int $id): RedirectResponse
    {
        $category = Category::onlyTrashed()->findOrFail($id);

        if ($category->parent_id && ! Category::whereKey($category->parent_id)->exists()) {
            return back()->with('error', 'Restore the parent category first.');
        }

        $category->restore();

        return back()->with('status', "Category \"{$category->name}\" restored.");
    }
}
