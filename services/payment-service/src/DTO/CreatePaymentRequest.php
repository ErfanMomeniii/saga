<?php

namespace PaymentService\DTO;

class CreatePaymentRequest
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        public float $amount
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['orderId'] ?? '',
            $data['customerId'] ?? '',
            (float) ($data['amount'] ?? 0)
        );
    }

    public function validate(): array
    {
        $errors = [];
        if (empty($this->orderId)) {
            $errors[] = 'orderId is required';
        }
        if (empty($this->customerId)) {
            $errors[] = 'customerId is required';
        }
        if ($this->amount <= 0) {
            $errors[] = 'amount must be greater than 0';
        }
        return $errors;
    }
}