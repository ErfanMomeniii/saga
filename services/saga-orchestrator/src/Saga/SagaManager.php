<?php

namespace SagaOrchestrator\Saga;

use SagaOrchestrator\Compensation\InventoryCompensation;
use SagaOrchestrator\Compensation\PaymentCompensation;
use SagaOrchestrator\Repository\SagaRepository;
use Shared\Http\Client;
use Shared\Utils\IdGenerator;

class SagaManager
{
    private SagaRepository $repository;
    private Client $orderClient;
    private Client $inventoryClient;
    private Client $paymentClient;
    private InventoryCompensation $inventoryCompensation;
    private PaymentCompensation $paymentCompensation;

    public function __construct(
        string $orderServiceUrl,
        string $inventoryServiceUrl,
        string $paymentServiceUrl,
        ?SagaRepository $repository = null
    ) {
        $this->repository = $repository ?? new SagaRepository();
        $this->orderClient = new Client($orderServiceUrl);
        $this->inventoryClient = new Client($inventoryServiceUrl);
        $this->paymentClient = new Client($paymentServiceUrl);
        $this->inventoryCompensation = new InventoryCompensation($inventoryServiceUrl);
        $this->paymentCompensation = new PaymentCompensation($paymentServiceUrl);
    }

    public function createSaga(array $orderData): SagaState
    {
        $sagaId = IdGenerator::generateSagaId();

        $saga = new SagaState(
            $sagaId,
            'ORDER_SAGA',
            'PENDING',
            $orderData,
            [
                ['name' => 'create_order', 'status' => 'PENDING'],
                ['name' => 'reserve_inventory', 'status' => 'PENDING'],
                ['name' => 'process_payment', 'status' => 'PENDING'],
                ['name' => 'complete_order', 'status' => 'PENDING'],
            ],
            date('c'),
            date('c'),
            0,
            0
        );

        $this->repository->save($saga);
        return $saga;
    }

    public function executeSaga(string $sagaId): SagaState
    {
        $saga = $this->repository->findById($sagaId);
        if (!$saga) {
            throw new \RuntimeException("Saga not found: $sagaId");
        }

        $saga->status = 'IN_PROGRESS';
        $saga->updatedAt = date('c');

        try {
            while ($saga->currentStep < count($saga->steps)) {
                $currentStep = $saga->steps[$saga->currentStep];
                $currentStep['status'] = 'IN_PROGRESS';
                $saga->steps[$saga->currentStep] = $currentStep;
                $this->repository->save($saga);

                $result = $this->executeStep($saga);

                if (!$result['success']) {
                    $saga->status = 'FAILED';
                    $currentStep['status'] = 'FAILED';
                    $saga->steps[$saga->currentStep] = $currentStep;
                    $this->repository->save($saga);
                    $this->rollback($saga);
                    return $saga;
                }

                $currentStep['status'] = 'COMPLETED';
                $currentStep['result'] = $result['data'];
                $saga->steps[$saga->currentStep] = $currentStep;
                $saga->currentStep++;
                $saga->updatedAt = date('c');
                $this->repository->save($saga);
            }

            $saga->status = 'COMPLETED';
            $saga->updatedAt = date('c');
            $this->repository->save($saga);
            return $saga;

        } catch (\Exception $e) {
            $saga->status = 'FAILED';
            $saga->updatedAt = date('c');
            $this->repository->save($saga);
            $this->rollback($saga);
            throw $e;
        }
    }

    private function executeStep(SagaState $saga): array
    {
        $stepIndex = $saga->currentStep;
        $stepName = $saga->steps[$stepIndex]['name'];

        return match ($stepName) {
            'create_order' => $this->executeCreateOrder($saga),
            'reserve_inventory' => $this->executeReserveInventory($saga),
            'process_payment' => $this->executeProcessPayment($saga),
            'complete_order' => $this->executeCompleteOrder($saga),
            default => ['success' => false, 'data' => []],
        };
    }

    private function executeCreateOrder(SagaState $saga): array
    {
        $orderData = $saga->data;
        $response = $this->orderClient->post('/orders', $orderData);
        $success = $response->getStatusCode() === 201;

        if ($success && isset($response->data['id'])) {
            $saga->data['orderId'] = $response->data['id'];
            $this->repository->save($saga);
        }

        return [
            'success' => $success,
            'data' => $response->data ?? [],
        ];
    }

    private function executeReserveInventory(SagaState $saga): array
    {
        $orderId = $saga->data['orderId'] ?? null;
        $items = $saga->data['items'] ?? [];

        if (!$orderId) {
            return ['success' => false, 'data' => ['error' => 'No orderId']];
        }

        $reserveItems = [];
        foreach ($items as $item) {
            $reserveItems[] = [
                'productId' => $item['productId'],
                'quantity' => $item['quantity']
            ];
        }

        $response = $this->inventoryClient->post('/inventory/reserve', [
            'orderId' => $orderId,
            'items' => $reserveItems,
        ]);
        $success = $response->getStatusCode() === 200;

        return [
            'success' => $success,
            'data' => $response->data ?? [],
        ];
    }

    private function executeProcessPayment(SagaState $saga): array
    {
        $paymentData = [
            'orderId' => $saga->data['orderId'],
            'customerId' => $saga->data['customerId'],
            'amount' => $saga->data['totalAmount'],
        ];

        $response = $this->paymentClient->post('/payment/process', $paymentData);
        $success = $response->getStatusCode() === 201;

        return [
            'success' => $success,
            'data' => $response->data ?? [],
        ];
    }

    private function executeCompleteOrder(SagaState $saga): array
    {
        $orderId = $saga->data['orderId'] ?? null;

        $response = $this->orderClient->put('/orders/status', [
            'orderId' => $orderId,
            'status' => 'COMPLETED',
        ]);
        $success = $response->getStatusCode() === 200;

        return [
            'success' => $success,
            'data' => $response->data ?? [],
        ];
    }

    public function rollback(SagaState $saga): void
    {
        for ($i = $saga->currentStep - 1; $i >= 0; $i--) {
            $stepName = $saga->steps[$i]['name'];

            if ($stepName === 'reserve_inventory') {
                $orderId = $saga->data['orderId'] ?? null;
                if ($orderId) {
                    $this->inventoryCompensation->releaseInventory($orderId);
                }
            }

            if ($stepName === 'process_payment') {
                $orderId = $saga->data['orderId'] ?? null;
                if ($orderId) {
                    $this->paymentCompensation->refundPayment($orderId);
                }
            }
        }

        if (isset($saga->data['orderId'])) {
            $this->orderClient->put('/orders/status', [
                'orderId' => $saga->data['orderId'],
                'status' => 'CANCELLED',
            ]);
        }
    }

    public function getSaga(string $id): ?SagaState
    {
        return $this->repository->findById($id);
    }

    public function resumeSaga(string $sagaId): SagaState
    {
        $saga = $this->repository->findById($sagaId);
        if (!$saga) {
            throw new \RuntimeException("Saga not found: $sagaId");
        }

        if ($saga->status === 'COMPLETED') {
            return $saga;
        }

        $saga->retryCount++;
        return $this->executeSaga($sagaId);
    }
}