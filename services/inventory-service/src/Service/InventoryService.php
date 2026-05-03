<?php

namespace InventoryService\Service;

use InventoryService\Domain\InventoryItem;
use InventoryService\DTO\ReserveInventoryRequest;
use InventoryService\Repository\InventoryRepository;

class InventoryService
{
    private InventoryRepository $repository;

    public function __construct()
    {
        $this->repository = new InventoryRepository();
    }

    public function reserve(ReserveInventoryRequest $request): array
    {
        $errors = $request->validate();
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        $reserved = [];
        $failed = [];

        foreach ($request->items as $item) {
            $productId = $item['productId'];
            $quantity = $item['quantity'] ?? 1;

            $success = $this->repository->reserve($productId, $request->orderId, $quantity);
            if ($success) {
                $reserved[] = $productId;
            } else {
                $failed[] = $productId;
            }
        }

        if (!empty($failed)) {
            foreach ($reserved as $productId) {
                $this->repository->release($productId, $request->orderId);
            }
            throw new \RuntimeException('Insufficient inventory for: ' . implode(', ', $failed));
        }

        return $reserved;
    }

    public function release(string $orderId): void
    {
        $items = $this->repository->findAll();
        foreach ($items as $item) {
            if ($item->orderId === $orderId && $item->status->value === 'RESERVED') {
                $this->repository->release($item->productId, $orderId);
            }
        }
    }

    public function getItem(string $productId): ?InventoryItem
    {
        return $this->repository->findById($productId);
    }

    public function getAllItems(): array
    {
        return $this->repository->findAll();
    }
}