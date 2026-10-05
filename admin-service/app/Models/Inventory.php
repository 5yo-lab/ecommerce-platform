<?php

namespace App\Models;

use App\Observers\InventoryObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'quantity', 'low_stock_threshold'])]
#[ObservedBy(InventoryObserver::class)]
class Inventory extends Model
{
    public const CREATED_AT = null;

    protected $table = 'inventory';

    protected $primaryKey = 'product_id';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function isLow(): bool
    {
        return $this->quantity <= $this->low_stock_threshold;
    }
}
