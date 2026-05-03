<?php

namespace InventoryService\Domain;

class InventoryItem
{
    public function __construct(
        public string $id,
        public string $productId,
        public string $productName,
        public int $quantity,
        public InventoryStatus $status,
        public ?string $orderId = null,
        public string $reservedAt = '',
        public string $createdAt = ''
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'productId' => $this->productId,
            'productName' => $this->productName,
            'quantity' => $this->quantity,
            'status' => $this->status->value,
            'orderId' => $this->orderId,
            'reservedAt' => $this->reservedAt,
            'createdAt' => $this->createdAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['productId'],
            $data['productName'],
            (int) $data['quantity'],
            InventoryStatus::from($data['status']),
            $data['orderId'] ?? null,
            $data['reservedAt'] ?? '',
            $data['createdAt'] ?? ''
        );
    }
}