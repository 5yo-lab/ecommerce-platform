<?php

namespace App\Observers;

use App\Jobs\SyncProductToGateway;
use App\Models\Inventory;

class InventoryObserver
{
    public function saved(Inventory $inventory): void
    {
        SyncProductToGateway::dispatch($inventory->product_id, 'updated')->afterCommit();
    }
}
