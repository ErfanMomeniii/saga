<?php

namespace InventoryService\DTO;

class ReserveInventoryRequest
{
    public function __construct(
        public string $orderId,
        public array $items
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['orderId'] ?? '',
            $data['items'] ?? []
        );
    }

    public function validate(): array
    {
        $errors = [];
        if (empty($this->orderId)) {
            $errors[] = 'orderId is required';
        }
        if (empty($this->items)) {
            $errors[] = 'items are required';
        }
        return $errors;
    }
}