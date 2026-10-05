<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'is_active' => $this->is_active,
            'image_url' => $this->image_url,
            'thumbnail_url' => $this->thumbnail_url,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'inventory' => $this->whenLoaded('inventory', fn () => [
                'quantity' => $this->inventory?->quantity ?? 0,
                'low_stock_threshold' => $this->inventory?->low_stock_threshold,
                'is_low' => (bool) $this->inventory?->isLow(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
