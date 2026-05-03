<?php

namespace InventoryService\Repository;

use InventoryService\Domain\InventoryItem;
use InventoryService\Domain\InventoryStatus;
use Shared\Database\Database;

class InventoryRepository
{
    public function save(InventoryItem $item): void
    {
        try {
            $sql = "INSERT OR IGNORE INTO inventory (product_id, product_name, quantity, status, order_id, reserved_at, created_at) 
                   VALUES (:product_id, :product_name, :quantity, :status, :order_id, :reserved_at, :created_at)";
            Database::execute($sql, [
                ':product_id' => $item->productId,
                ':product_name' => $item->productName,
                ':quantity' => $item->quantity,
                ':status' => $item->status->value,
                ':order_id' => $item->orderId ?? 'NULL',
                ':reserved_at' => $item->reservedAt ?? 'NULL',
                ':created_at' => $item->createdAt ?: date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {}
    }

    public function findById(string $productId): ?InventoryItem
    {
        $rows = Database::query("SELECT * FROM inventory WHERE product_id = :product_id", [':product_id' => $productId]);
        
        if (empty($rows)) {
            return null;
        }

        return $this->rowToItem($rows[0]);
    }

    public function findAll(): array
    {
        $rows = Database::query("SELECT * FROM inventory");
        
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->rowToItem($row);
        }
        
        return $items;
    }

    public function reserve(string $productId, string $orderId, int $quantity): bool
    {
        $sql = "UPDATE inventory 
               SET quantity = quantity - :qty, status = :status, order_id = :order_id, reserved_at = :reserved_at 
               WHERE product_id = :product_id AND status = 'AVAILABLE' AND quantity >= :qty";

        $affected = Database::execute($sql, [
            ':qty' => $quantity,
            ':status' => InventoryStatus::RESERVED->value,
            ':order_id' => $orderId,
            ':reserved_at' => date('Y-m-d H:i:s'),
            ':product_id' => $productId
        ]);

        return $affected > 0;
    }

    public function release(string $productId, string $orderId): bool
    {
        $sql = "UPDATE inventory 
               SET status = 'AVAILABLE', order_id = NULL, reserved_at = NULL 
               WHERE product_id = :product_id AND order_id = :order_id";

        $affected = Database::execute($sql, [
            ':product_id' => $productId,
            ':order_id' => $orderId
        ]);

        return $affected > 0;
    }

    private function rowToItem(array $row): InventoryItem
    {
        return new InventoryItem(
            $row['id'] ?? 0,
            $row['product_id'],
            $row['product_name'],
            (int) $row['quantity'],
            InventoryStatus::from($row['status']),
            $row['order_id'] ?? null,
            $row['reserved_at'] ?? '',
            $row['created_at'] ?? ''
        );
    }
}