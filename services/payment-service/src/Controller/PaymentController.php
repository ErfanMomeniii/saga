<?php

namespace PaymentService\Controller;

use PaymentService\DTO\CreatePaymentRequest;
use PaymentService\Service\PaymentService;
use Shared\Http\Response;

class PaymentController
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function process(array $data): Response
    {
        try {
            $request = CreatePaymentRequest::fromArray($data);
            $payment = $this->paymentService->processPayment($request);
            return Response::success($payment->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    public function refund(array $data): Response
    {
        try {
            $orderId = $data['orderId'] ?? '';
            if (empty($orderId)) {
                return Response::error('orderId is required', 400);
            }
            $payment = $this->paymentService->refund($orderId);
            if (!$payment) {
                return Response::error('Payment not found or already refunded', 404);
            }
            return Response::success(['message' => 'Payment refunded', 'payment' => $payment->toArray()]);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    public function get(string $orderId): Response
    {
        $payment = $this->paymentService->getPayment($orderId);
        if (!$payment) {
            return Response::error('Payment not found', 404);
        }
        return Response::success($payment->toArray());
    }
}