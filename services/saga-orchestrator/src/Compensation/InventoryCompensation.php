<?php

namespace SagaOrchestrator\Compensation;

use Shared\Http\Client;

class InventoryCompensation
{
    private Client $httpClient;

    public function __construct(string $inventoryServiceUrl)
    {
        $this->httpClient = new Client($inventoryServiceUrl);
    }

    public function releaseInventory(string $orderId): bool
    {
        try {
            $response = $this->httpClient->post('/inventory/release', [
                'orderId' => $orderId,
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}