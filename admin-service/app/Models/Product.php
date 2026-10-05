<?php

namespace App\Models;

use App\Observers\ProductObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['category_id', 'sku', 'name', 'slug', 'description', 'price', 'is_active', 'image_path', 'thumbnail_path'])]
#[ObservedBy(ProductObserver::class)]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image_path ? Storage::disk('public')->url($this->image_path) : null);
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn () => $this->thumbnail_path
            ? Storage::disk('public')->url($this->thumbnail_path)
            : $this->image_url);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $term) => $q->where(fn (Builder $w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")))
            ->when($filters['category_id'] ?? null, fn (Builder $q, $id) => $q->where('category_id', $id))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $q) => $q->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $q) => $q->where('is_active', false))
            ->when(($filters['stock'] ?? null) === 'low', fn (Builder $q) => $q->whereHas('inventory',
                fn (Builder $i) => $i->whereColumn('quantity', '<=', 'low_stock_threshold')->where('quantity', '>', 0)))
            ->when(($filters['stock'] ?? null) === 'out', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereDoesntHave('inventory')
                ->orWhereHas('inventory', fn (Builder $i) => $i->where('quantity', 0))))
            ->when(($filters['trashed'] ?? null) === 'only', fn (Builder $q) => $q->onlyTrashed());
    }
}
