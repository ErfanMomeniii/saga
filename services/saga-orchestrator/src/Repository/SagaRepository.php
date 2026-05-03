<?php

namespace SagaOrchestrator\Repository;

use SagaOrchestrator\Saga\SagaState;
use Shared\Database\Database;

class SagaRepository
{
    public function save(SagaState $saga): void
    {
        $existing = $this->findById($saga->id);
        
        if ($existing) {
            $sql = "UPDATE sagas SET 
                   status = :status, data = :data, steps = :steps, 
                   current_step = :current_step, retry_count = :retry_count, updated_at = :updated_at
                   WHERE id = :id";
            Database::execute($sql, [
                ':status' => $saga->status,
                ':data' => json_encode($saga->data),
                ':steps' => json_encode($saga->steps),
                ':current_step' => $saga->currentStep,
                ':retry_count' => $saga->retryCount,
                ':updated_at' => $saga->updatedAt,
                ':id' => $saga->id
            ]);
        } else {
            $sql = "INSERT INTO sagas (id, type, status, data, steps, current_step, retry_count, created_at, updated_at) 
                   VALUES (:id, :type, :status, :data, :steps, :current_step, :retry_count, :created_at, :updated_at)";
            Database::execute($sql, [
                ':id' => $saga->id,
                ':type' => $saga->type,
                ':status' => $saga->status,
                ':data' => json_encode($saga->data),
                ':steps' => json_encode($saga->steps),
                ':current_step' => $saga->currentStep,
                ':retry_count' => $saga->retryCount,
                ':created_at' => $saga->createdAt,
                ':updated_at' => $saga->updatedAt
            ]);
        }
    }

    public function findById(string $id): ?SagaState
    {
        $rows = Database::query("SELECT * FROM sagas WHERE id = :id", [':id' => $id]);
        
        if (empty($rows)) {
            return null;
        }

        return $this->rowToSaga($rows[0]);
    }

    public function findByOrderId(string $orderId): ?SagaState
    {
        $rows = Database::query("SELECT * FROM sagas", []);
        
        foreach ($rows as $row) {
            $data = json_decode($row['data'], true);
            if (($data['orderId'] ?? $data['order_id'] ?? null) === $orderId) {
                return $this->rowToSaga($row);
            }
        }
        
        return null;
    }

    public function findAll(): array
    {
        $rows = Database::query("SELECT * FROM sagas ORDER BY created_at DESC");
        
        $sagas = [];
        foreach ($rows as $row) {
            $sagas[] = $this->rowToSaga($row);
        }
        
        return $sagas;
    }

    private function rowToSaga(array $row): SagaState
    {
        return new SagaState(
            $row['id'],
            $row['type'],
            $row['status'],
            json_decode($row['data'], true),
            json_decode($row['steps'], true),
            $row['created_at'],
            $row['updated_at'],
            (int) $row['current_step'],
            (int) $row['retry_count']
        );
    }
}