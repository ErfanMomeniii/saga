<?php

namespace Shared\Utils;

class IdGenerator
{
    public static function generate(string $prefix = ''): string
    {
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
        return $prefix ? "{$prefix}_{$uuid}" : $uuid;
    }

    public static function generateOrderId(): string
    {
        return self::generate('ORD');
    }

    public static function generateTransactionId(): string
    {
        return self::generate('TXN');
    }

    public static function generateSagaId(): string
    {
        return self::generate('SAGA');
    }
}