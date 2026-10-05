<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

#[Fillable(['parent_id', 'name', 'slug'])]
class Category extends Model
{
    use HasFactory, SoftDeletes;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('name');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive')->withCount('products');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Ids of every category below this one, used to prevent parent cycles.
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $frontier = [$this->id];

        while ($frontier !== []) {
            $frontier = static::whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }

    /**
     * Flattened tree for <select> inputs: [id => '— — Name'].
     *
     * @param  list<int>  $exclude
     * @return Collection<int, string>
     */
    public static function treeOptions(array $exclude = []): Collection
    {
        $all = static::orderBy('name')->get(['id', 'parent_id', 'name'])->groupBy('parent_id');
        $options = collect();

        $walk = function ($parentId, int $depth) use (&$walk, $all, $options, $exclude): void {
            foreach ($all->get($parentId, collect()) as $category) {
                if (in_array($category->id, $exclude, true)) {
                    continue;
                }
                $options->put($category->id, str_repeat('— ', $depth).$category->name);
                $walk($category->id, $depth + 1);
            }
        };
        $walk(null, 0);

        return $options;
    }
}
