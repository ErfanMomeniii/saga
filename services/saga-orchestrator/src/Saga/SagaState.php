<?php

namespace SagaOrchestrator\Saga;

class SagaState
{
    public function __construct(
        public string $id,
        public string $type,
        public string $status,
        public array $data,
        public array $steps,
        public string $createdAt,
        public string $updatedAt,
        public ?int $currentStep = 0,
        public ?int $retryCount = 0
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'data' => $this->data,
            'steps' => $this->steps,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'currentStep' => $this->currentStep,
            'retryCount' => $this->retryCount,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['type'],
            $data['status'],
            $data['data'],
            $data['steps'],
            $data['createdAt'],
            $data['updatedAt'],
            $data['currentStep'] ?? 0,
            $data['retryCount'] ?? 0
        );
    }
}