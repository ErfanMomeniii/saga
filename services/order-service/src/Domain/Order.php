<?php

namespace OrderService\Domain;

class Order
{
    public function __construct(
        public string $id,
        public string $customerId,
        public array $items,
        public float $totalAmount,
        public OrderStatus $status,
        public string $createdAt,
        public string $updatedAt,
        public ?string $sagaId = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customerId' => $this->customerId,
            'items' => $this->items,
            'totalAmount' => $this->totalAmount,
            'status' => $this->status->value,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'sagaId' => $this->sagaId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['customerId'],
            $data['items'],
            (float) $data['totalAmount'],
            OrderStatus::from($data['status']),
            $data['createdAt'],
            $data['updatedAt'],
            $data['sagaId'] ?? null
        );
    }
}