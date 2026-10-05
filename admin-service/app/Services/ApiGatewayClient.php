<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * HTTP client for the NestJS API gateway: central login and the API-key protected internal endpoints.
 */
class ApiGatewayClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
        private readonly int $timeout,
    ) {}

    /**
     * @return array{accessToken: string, expiresIn: int, user: array{id: string, name: string, email: string, role: string}}|null
     *                                                                                                                             null when the credentials are rejected
     *
     * @throws ConnectionException when the user service is unreachable
     */
    public function login(string $email, string $password): ?array
    {
        $response = $this->http()->post('/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        if ($response->unauthorized() || $response->status() === 400) {
            return null;
        }

        return $response->throw()->json();
    }

    /**
     * @throws RequestException|ConnectionException
     */
    public function syncProduct(int $productId, string $event): array
    {
        return $this->http()
            ->withHeaders(['X-API-Key' => (string) $this->apiKey])
            ->post('/api/internal/products/sync', [
                'productId' => $productId,
                'event' => $event,
            ])
            ->throw()
            ->json();
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout);
    }
}
