<?php

namespace PaymentService\Service;

use PaymentService\Domain\Payment;
use PaymentService\Domain\PaymentStatus;
use PaymentService\DTO\CreatePaymentRequest;
use PaymentService\Repository\PaymentRepository;
use Shared\Utils\IdGenerator;

class PaymentService
{
    private PaymentRepository $repository;

    public function __construct()
    {
        $this->repository = new PaymentRepository();
    }

    public function processPayment(CreatePaymentRequest $request): Payment
    {
        $errors = $request->validate();
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        $paymentId = IdGenerator::generate('PAY');
        $transactionId = IdGenerator::generateTransactionId();

        $payment = new Payment(
            $paymentId,
            $request->orderId,
            $request->customerId,
            $request->amount,
            PaymentStatus::COMPLETED,
            date('Y-m-d H:i:s'),
            $transactionId
        );

        $this->repository->save($payment);
        return $payment;
    }

    public function refund(string $orderId): ?Payment
    {
        $payment = $this->repository->findByOrderId($orderId);
        if (!$payment || $payment->status !== PaymentStatus::COMPLETED) {
            return null;
        }

        $refundId = IdGenerator::generate('REF');
        $payment->status = PaymentStatus::REFUNDED;
        $payment->refundId = $refundId;
        $this->repository->save($payment);
        return $payment;
    }

    public function getPayment(string $orderId): ?Payment
    {
        return $this->repository->findByOrderId($orderId);
    }
}