<?php

namespace SagaOrchestrator\Saga;

use SagaOrchestrator\Repository\SagaRepository;
use Shared\Http\Client;

class OrderSaga
{
    private SagaManager $sagaManager;

    public function __construct(
        string $orderServiceUrl,
        string $inventoryServiceUrl,
        string $paymentServiceUrl
    ) {
        $this->sagaManager = new SagaManager(
            $orderServiceUrl,
            $inventoryServiceUrl,
            $paymentServiceUrl
        );
    }

    public function start(array $orderData): array
    {
        $saga = $this->sagaManager->createSaga($orderData);

        try {
            $result = $this->sagaManager->executeSaga($saga->id);

            return [
                'success' => $result->status === 'COMPLETED',
                'sagaId' => $result->id,
                'data' => $result->data,
            ];
        } catch (\Exception $e) {
            $saga = $this->sagaManager->getSaga($saga->id);
            return [
                'success' => false,
                'sagaId' => $saga->id,
                'error' => $e->getMessage(),
                'status' => $saga->status ?? 'FAILED',
            ];
        }
    }

    public function getStatus(string $sagaId): ?SagaState
    {
        return $this->sagaManager->getSaga($sagaId);
    }

    public function retry(string $sagaId): array
    {
        try {
            $result = $this->sagaManager->resumeSaga($sagaId);
            return [
                'success' => $result->status === 'COMPLETED',
                'sagaId' => $result->id,
                'status' => $result->status,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function compensate(string $sagaId): array
    {
        $saga = $this->sagaManager->getSaga($sagaId);
        if (!$saga) {
            return ['success' => false, 'error' => 'Saga not found'];
        }

        $this->sagaManager->rollback($saga);
        return ['success' => true];
    }
}