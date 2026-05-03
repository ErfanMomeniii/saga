<?php

namespace OrderService\DTO;

class CreateOrderRequest
{
    public function __construct(
        public string $customerId,
        public array $items
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['customerId'] ?? '',
            $data['items'] ?? []
        );
    }

    public function validate(): array
    {
        $errors = [];
        if (empty($this->customerId)) {
            $errors[] = 'customerId is required';
        }
        if (empty($this->items)) {
            $errors[] = 'items are required';
        }
        return $errors;
    }
}