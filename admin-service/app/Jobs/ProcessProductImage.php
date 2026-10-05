<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ProcessProductImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public const THUMBNAIL_SIZE = 300;

    public function __construct(
        public readonly int $productId,
        public readonly string $imagePath,
    ) {}

    public function handle(): void
    {
        $product = Product::withTrashed()->find($this->productId);

        if (! $product || $product->image_path !== $this->imagePath) {
            return;
        }

        $disk = Storage::disk('public');
        $thumbnailPath = 'products/thumbnails/'.pathinfo($this->imagePath, PATHINFO_FILENAME).'.jpg';

        $thumbnail = ImageManager::gd()
            ->read($disk->path($this->imagePath))
            ->cover(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE)
            ->toJpeg(80);

        $disk->put($thumbnailPath, (string) $thumbnail);

        $product->update(['thumbnail_path' => $thumbnailPath]);
    }
}
