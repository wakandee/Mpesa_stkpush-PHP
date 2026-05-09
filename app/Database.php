<?php

declare(strict_types=1);

namespace App;

use App\Support\Env;
use mysqli;
use RuntimeException;

final class Database
{
    private static ?mysqli $connection = null;

    public static function connection(?string $databaseOverride = null): mysqli
    {
        if (self::$connection instanceof mysqli && $databaseOverride === null) {
            return self::$connection;
        }

        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = (int) Env::get('DB_PORT', '3306');
        $username = Env::get('DB_USERNAME', 'root');
        $password = Env::get('DB_PASSWORD', '');
        $database = $databaseOverride ?? Env::get('DB_DATABASE', 'mpesa_demo');

        $connection = @new mysqli($host, $username, $password, $database, $port);

        if ($connection->connect_errno !== 0) {
            throw new RuntimeException('Database connection failed: ' . $connection->connect_error);
        }

        $connection->set_charset('utf8mb4');

        if ($databaseOverride === null) {
            self::$connection = $connection;
        }

        return $connection;
    }

    public static function connectionWithoutDatabase(): mysqli
    {
        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = (int) Env::get('DB_PORT', '3306');
        $username = Env::get('DB_USERNAME', 'root');
        $password = Env::get('DB_PASSWORD', '');

        $connection = @new mysqli($host, $username, $password, '', $port);

        if ($connection->connect_errno !== 0) {
            throw new RuntimeException('Database server connection failed: ' . $connection->connect_error);
        }

        $connection->set_charset('utf8mb4');

        return $connection;
    }
}
