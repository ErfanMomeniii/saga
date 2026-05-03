<?php

namespace SagaOrchestrator\Compensation;

use Shared\Http\Client;

class PaymentCompensation
{
    private Client $httpClient;

    public function __construct(string $paymentServiceUrl)
    {
        $this->httpClient = new Client($paymentServiceUrl);
    }

    public function refundPayment(string $orderId): bool
    {
        try {
            $response = $this->httpClient->post('/payment/refund', [
                'orderId' => $orderId,
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}