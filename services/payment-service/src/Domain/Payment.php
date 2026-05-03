<?php

namespace PaymentService\Domain;

class Payment
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $customerId,
        public float $amount,
        public PaymentStatus $status,
        public string $createdAt,
        public ?string $transactionId = null,
        public ?string $refundId = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'orderId' => $this->orderId,
            'customerId' => $this->customerId,
            'amount' => $this->amount,
            'status' => $this->status->value,
            'createdAt' => $this->createdAt,
            'transactionId' => $this->transactionId,
            'refundId' => $this->refundId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['orderId'],
            $data['customerId'],
            (float) $data['amount'],
            PaymentStatus::from($data['status']),
            $data['createdAt'],
            $data['transactionId'] ?? null,
            $data['refundId'] ?? null
        );
    }
}