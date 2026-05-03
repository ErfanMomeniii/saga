<?php

namespace Shared\Database;

class Database
{
    private static ?\PDO $connection = null;
    private static string $path;

    public static function configure(array $config): void
    {
        if (isset($config['path'])) {
            self::$path = $config['path'];
        } else {
            self::$path = self::getDefaultPath();
        }
    }

    private static function getDefaultPath(): string
    {
        $storageDir = __DIR__ . '/../../storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        return $storageDir . '/database.sqlite';
    }

    public static function getConnection(): \PDO
    {
        if (self::$connection === null) {
            $dir = dirname(self::$path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            self::$connection = new \PDO('sqlite:' . self::$path);
            self::$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::initialize();
        }

        return self::$connection;
    }

    private static function initialize(): void
    {
        $db = self::$connection;

        $db->exec("CREATE TABLE IF NOT EXISTS orders (
            id TEXT PRIMARY KEY,
            customer_id TEXT NOT NULL,
            items TEXT NOT NULL,
            total_amount REAL NOT NULL,
            status TEXT NOT NULL DEFAULT 'PENDING',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            saga_id TEXT
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS inventory (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id TEXT UNIQUE NOT NULL,
            product_name TEXT NOT NULL,
            quantity INTEGER NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'AVAILABLE',
            order_id TEXT,
            reserved_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS payments (
            id TEXT PRIMARY KEY,
            order_id TEXT NOT NULL,
            customer_id TEXT NOT NULL,
            amount REAL NOT NULL,
            status TEXT NOT NULL DEFAULT 'PENDING',
            transaction_id TEXT,
            refund_id TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS sagas (
            id TEXT PRIMARY KEY,
            type TEXT NOT NULL,
            status TEXT NOT NULL,
            data TEXT NOT NULL,
            steps TEXT NOT NULL,
            current_step INTEGER NOT NULL DEFAULT 0,
            retry_count INTEGER NOT NULL DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $db->query("SELECT COUNT(*) FROM inventory");
        $count = $stmt->fetchColumn();
        if ($count == 0) {
            $db->exec("INSERT INTO inventory (product_id, product_name, quantity, status) VALUES 
                ('PROD-001', 'Laptop', 100, 'AVAILABLE'),
                ('PROD-002', 'Phone', 50, 'AVAILABLE'),
                ('PROD-003', 'Tablet', 30, 'AVAILABLE')");
        }
    }

    public static function query(string $sql, array $params = []): array
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function execute(string $sql, array $params = []): int
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    public static function begin(): void
    {
        self::getConnection()->beginTransaction();
    }

    public static function commit(): void
    {
        self::getConnection()->commit();
    }

    public static function rollback(): void
    {
        self::getConnection()->rollBack();
    }
}