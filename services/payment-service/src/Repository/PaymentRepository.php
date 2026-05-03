<?php

namespace PaymentService\Repository;

use PaymentService\Domain\Payment;
use PaymentService\Domain\PaymentStatus;
use Shared\Database\Database;

class PaymentRepository
{
    public function save(Payment $payment): void
    {
        try {
            $sql = "INSERT OR IGNORE INTO payments (id, order_id, customer_id, amount, status, transaction_id, refund_id, created_at) 
                   VALUES (:id, :order_id, :customer_id, :amount, :status, :transaction_id, :refund_id, :created_at)";
            Database::execute($sql, [
                ':id' => $payment->id,
                ':order_id' => $payment->orderId,
                ':customer_id' => $payment->customerId,
                ':amount' => $payment->amount,
                ':status' => $payment->status->value,
                ':transaction_id' => $payment->transactionId ?? 'NULL',
                ':refund_id' => $payment->refundId ?? 'NULL',
                ':created_at' => $payment->createdAt
            ]);
        } catch (\Exception $e) {}
    }

    public function findById(string $id): ?Payment
    {
        $rows = Database::query("SELECT * FROM payments WHERE id = :id", [':id' => $id]);
        
        if (empty($rows)) {
            return null;
        }

        return $this->rowToPayment($rows[0]);
    }

    public function findByOrderId(string $orderId): ?Payment
    {
        $rows = Database::query("SELECT * FROM payments WHERE order_id = :order_id", [':order_id' => $orderId]);
        
        if (empty($rows)) {
            return null;
        }

        return $this->rowToPayment($rows[0]);
    }

    public function findAll(): array
    {
        $rows = Database::query("SELECT * FROM payments");
        
        $payments = [];
        foreach ($rows as $row) {
            $payments[] = $this->rowToPayment($row);
        }
        
        return $payments;
    }

    private function rowToPayment(array $row): Payment
    {
        return new Payment(
            $row['id'],
            $row['order_id'],
            $row['customer_id'],
            (float) $row['amount'],
            PaymentStatus::from($row['status']),
            $row['created_at'],
            $row['transaction_id'] ?? null,
            $row['refund_id'] ?? null
        );
    }
}