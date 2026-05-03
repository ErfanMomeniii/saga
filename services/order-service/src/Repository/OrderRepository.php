<?php

namespace OrderService\Repository;

use OrderService\Domain\Order;
use OrderService\Domain\OrderStatus;
use Shared\Database\Database;

class OrderRepository
{
    public function save(Order $order): void
    {
        try {
            $sql = "INSERT OR IGNORE INTO orders (id, customer_id, items, total_amount, status, created_at, updated_at, saga_id) 
                   VALUES (:id, :customer_id, :items, :total_amount, :status, :created_at, :updated_at, :saga_id)";
            Database::execute($sql, [
                ':id' => $order->id,
                ':customer_id' => $order->customerId,
                ':items' => json_encode($order->items),
                ':total_amount' => $order->totalAmount,
                ':status' => $order->status->value,
                ':created_at' => $order->createdAt,
                ':updated_at' => $order->updatedAt,
                ':saga_id' => $order->sagaId ?? ''
            ]);
        } catch (\Exception $e) {
            // ignore if exists
        }
    }

    public function findById(string $id): ?Order
    {
        $rows = Database::query("SELECT * FROM orders WHERE id = :id", [':id' => $id]);
        
        if (empty($rows)) {
            return null;
        }

        return $this->rowToOrder($rows[0]);
    }

    public function updateStatus(string $id, OrderStatus $status): void
    {
        Database::execute("UPDATE orders SET status = :status, updated_at = :updated_at WHERE id = :id", [
            ':status' => $status->value,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $id
        ]);
    }

    private function rowToOrder(array $row): Order
    {
        return new Order(
            $row['id'],
            $row['customer_id'],
            json_decode($row['items'], true),
            (float) $row['total_amount'],
            OrderStatus::from($row['status']),
            $row['created_at'],
            $row['updated_at'],
            $row['saga_id'] ?? null
        );
    }
}