<?php

namespace OrderService\Service;

use OrderService\Domain\Order;
use OrderService\Domain\OrderStatus;
use OrderService\DTO\CreateOrderRequest;
use OrderService\Repository\OrderRepository;
use Shared\Utils\IdGenerator;

class OrderService
{
    private OrderRepository $repository;

    public function __construct()
    {
        $this->repository = new OrderRepository();
    }

    public function createOrder(CreateOrderRequest $request): Order
    {
        $errors = $request->validate();
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        $orderId = IdGenerator::generateOrderId();
        $totalAmount = $this->calculateTotal($request->items);

        $order = new Order(
            $orderId,
            $request->customerId,
            $request->items,
            $totalAmount,
            OrderStatus::PENDING,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        );

        $this->repository->save($order);
        return $order;
    }

    public function getOrder(string $id): ?Order
    {
        return $this->repository->findById($id);
    }

    public function updateStatus(string $id, OrderStatus $status): void
    {
        $this->repository->updateStatus($id, $status);
    }

    private function calculateTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $price = $item['price'] ?? 0;
            $quantity = $item['quantity'] ?? 1;
            $total += $price * $quantity;
        }
        return $total;
    }
}