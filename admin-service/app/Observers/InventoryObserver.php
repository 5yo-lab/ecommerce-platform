<?php

namespace App\Observers;

use App\Jobs\SyncProductToGateway;
use App\Models\Inventory;

/** Stock is part of the gateway's cached product payload, so stock changes are synced too. */
class InventoryObserver
{
    public function saved(Inventory $inventory): void
    {
        SyncProductToGateway::dispatch($inventory->product_id, 'updated')->afterCommit();
    }
}
