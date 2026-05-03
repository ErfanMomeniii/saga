<?php

namespace SagaOrchestrator\Config;

class Config
{
    public static array $database = [
        'host' => 'localhost',
        'port' => '3306',
        'database' => 'saga_db',
        'username' => 'saga',
        'password' => 'sagapass'
    ];

    public static function load(): void
    {
        if ($host = getenv('DB_HOST')) self::$database['host'] = $host;
        if ($port = getenv('DB_PORT')) self::$database['port'] = $port;
        if ($name = getenv('DB_NAME')) self::$database['database'] = $name;
        if ($user = getenv('DB_USER')) self::$database['username'] = $user;
        if ($pass = getenv('DB_PASS')) self::$database['password'] = $pass;
    }
}