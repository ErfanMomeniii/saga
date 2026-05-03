<?php

namespace InventoryService\Controller;

use InventoryService\DTO\ReserveInventoryRequest;
use InventoryService\Service\InventoryService;
use Shared\Http\Response;

class InventoryController
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    public function reserve(array $data): Response
    {
        try {
            $request = ReserveInventoryRequest::fromArray($data);
            $reserved = $this->inventoryService->reserve($request);
            return Response::success([
                'orderId' => $request->orderId,
                'reserved' => $reserved,
            ]);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            return Response::error($e->getMessage(), 409);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    public function release(array $data): Response
    {
        try {
            $orderId = $data['orderId'] ?? '';
            if (empty($orderId)) {
                return Response::error('orderId is required', 400);
            }
            $this->inventoryService->release($orderId);
            return Response::success(['message' => 'Inventory released']);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    public function get(string $productId): Response
    {
        $item = $this->inventoryService->getItem($productId);
        if (!$item) {
            return Response::error('Product not found', 404);
        }
        return Response::success($item->toArray());
    }

    public function getAll(): Response
    {
        $items = $this->inventoryService->getAllItems();
        $data = array_map(fn($item) => $item->toArray(), $items);
        return Response::success($data);
    }
}