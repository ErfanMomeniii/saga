<?php

namespace SagaOrchestrator\Controller;

use SagaOrchestrator\Saga\OrderSaga;
use SagaOrchestrator\Saga\SagaState;
use Shared\Http\Response;

class SagaController
{
    private ?OrderSaga $orderSaga = null;
    private string $orderServiceUrl;
    private string $inventoryServiceUrl;
    private string $paymentServiceUrl;

    public function __construct(
        string $orderServiceUrl,
        string $inventoryServiceUrl,
        string $paymentServiceUrl
    ) {
        $this->orderServiceUrl = $orderServiceUrl;
        $this->inventoryServiceUrl = $inventoryServiceUrl;
        $this->paymentServiceUrl = $paymentServiceUrl;
    }

    private function getOrderSaga(): OrderSaga
    {
        if (!$this->orderSaga) {
            $this->orderSaga = new OrderSaga(
                $this->orderServiceUrl,
                $this->inventoryServiceUrl,
                $this->paymentServiceUrl
            );
        }
        return $this->orderSaga;
    }

    public function start(array $data): Response
    {
        try {
            $result = $this->getOrderSaga()->start($data);
            return Response::success($result);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    public function getStatus(string $sagaId): Response
    {
        $saga = $this->getOrderSaga()->getStatus($sagaId);
        if (!$saga) {
            return Response::error('Saga not found', 404);
        }
        return Response::success($saga->toArray());
    }

    public function retry(string $sagaId): Response
    {
        try {
            $result = $this->getOrderSaga()->retry($sagaId);
            return Response::success($result);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    public function compensate(array $data): Response
    {
        try {
            $sagaId = $data['sagaId'] ?? '';
            if (empty($sagaId)) {
                return Response::error('sagaId is required', 400);
            }
            $result = $this->getOrderSaga()->compensate($sagaId);
            return Response::success($result);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }
}