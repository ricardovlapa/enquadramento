<?php

namespace App\Service;

use PDO;
use RuntimeException;

class Database
{
    private static ?PDO $connection = null;
    private static bool $attempted = false;
    private static ?string $lastError = null;

    public static function getConnectionFromEnv(): ?PDO
    {
        return self::connectFromEnv(false);
    }

    public static function requireConnectionFromEnv(): PDO
    {
        $connection = self::connectFromEnv(true);
        if ($connection === null) {
            throw new RuntimeException(self::$lastError ?? 'Database connection failed.');
        }
        return $connection;
    }

    public static function getLastError(): ?string
    {
        return self::$lastError;
    }

    private static function connectFromEnv(bool $throwOnFailure): ?PDO
    {
        if (!self::$attempted) {
            self::$attempted = true;
            $dsn = getenv('DB_DSN') ?: getenv('MYSQL_DSN') ?: '';
            if ($dsn === '') {
                self::$lastError = 'DB_DSN (or MYSQL_DSN) environment variable is not set.';
                if ($throwOnFailure) {
                    throw new RuntimeException(self::$lastError);
                }
                return null;
            }

            $user = getenv('DB_USER') ?: getenv('MYSQL_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: getenv('MYSQL_PASS') ?: '';

            try {
                $pdo = new PDO($dsn, $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection = $pdo;
            } catch (\Exception $e) {
                self::$lastError = 'Failed to connect: ' . $e->getMessage();
                if ($throwOnFailure) {
                    throw new RuntimeException(self::$lastError, 0, $e);
                }
                self::$connection = null;
            }
        }

        if ($throwOnFailure && self::$connection === null) {
            throw new RuntimeException(self::$lastError ?? 'Database connection failed.');
        }

        return self::$connection;
    }
}
