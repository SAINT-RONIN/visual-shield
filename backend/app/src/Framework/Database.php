<?php

declare(strict_types=1);

namespace App\Framework;

use PDO;

/**
 * Singleton wrapper for the MySQL PDO connection.
 *
 * Reads connection credentials from environment variables and creates
 * a single shared PDO instance for the entire request lifecycle.
 */
class Database
{
    private static ?PDO $instance = null;

    /** Return the shared PDO instance, creating it on first call. */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host = getenv('MYSQL_HOST');
            $dbname = getenv('MYSQL_DATABASE');
            $user = getenv('MYSQL_USER');
            $password = getenv('MYSQL_PASSWORD');

            if (!$host || !$dbname || !$user || !$password) {
                throw new \RuntimeException('Missing required database environment variables');
            }

            self::$instance = new PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                $user,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }

        return self::$instance;
    }

    private function __construct() {}
}
