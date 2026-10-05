<?php

namespace App\Jobs;

use App\Services\ApiGatewayClient;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncProductToGateway implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $uniqueFor = 10;

    public function __construct(
        public readonly int $productId,
        public readonly string $event,
    ) {}

    public function backoff(): array
    {
        return [5, 15, 60, 300];
    }

    public function uniqueId(): string
    {
        return (string) $this->productId;
    }

    public function handle(ApiGatewayClient $gateway): void
    {
        $result = $gateway->syncProduct($this->productId, $this->event);

        Log::info('Product synced to API gateway', ['product_id' => $this->productId, 'event' => $this->event] + $result);
    }
}
