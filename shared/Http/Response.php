<?php

namespace Shared\Http;

class Response
{
    public function __construct(
        public array $data,
        public int $statusCode = 200,
        public array $headers = []
    ) {}

    public static function success(array $data, int $statusCode = 200): self
    {
        return new self($data, $statusCode);
    }

    public static function error(string $message, int $statusCode = 400): self
    {
        return new self(['error' => $message], $statusCode);
    }

    public function toJson(): string
    {
        return json_encode($this->data, JSON_PRETTY_PRINT);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}