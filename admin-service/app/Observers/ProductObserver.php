<?php

namespace App\Observers;

use App\Jobs\SyncProductToGateway;
use App\Models\Product;

class ProductObserver
{
    public function created(Product $product): void
    {
        SyncProductToGateway::dispatch($product->id, 'created')->afterCommit();
    }

    public function updated(Product $product): void
    {
        SyncProductToGateway::dispatch($product->id, 'updated')->afterCommit();
    }

    public function deleted(Product $product): void
    {
        SyncProductToGateway::dispatch($product->id, 'deleted')->afterCommit();
    }

    public function restored(Product $product): void
    {
        SyncProductToGateway::dispatch($product->id, 'restored')->afterCommit();
    }
}
