<?php

namespace OrderService\Controller;

use OrderService\DTO\CreateOrderRequest;
use OrderService\Domain\OrderStatus;
use OrderService\Service\OrderService;
use Shared\Http\Response;

class OrderController
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function create(array $data): Response
    {
        try {
            $request = CreateOrderRequest::fromArray($data);
            $order = $this->orderService->createOrder($request);
            return Response::success($order->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    public function get(string $id): Response
    {
        $order = $this->orderService->getOrder($id);
        if (!$order) {
            return Response::error('Order not found', 404);
        }
        return Response::success($order->toArray());
    }

    public function updateStatus(array $data): Response
    {
        try {
            $orderId = $data['orderId'] ?? '';
            $status = $data['status'] ?? '';
            if (empty($orderId) || empty($status)) {
                return Response::error('orderId and status are required', 400);
            }
            $this->orderService->updateStatus($orderId, OrderStatus::from($status));
            return Response::success(['message' => 'Status updated']);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 400);
        }
    }
}