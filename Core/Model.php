<?php

namespace Core;

use PDO;
use RuntimeException;

/**
 * Base model
 *
 * PHP version 7.0
 */
abstract class Model
{

    /**
     * Get the PDO database connection
     *
     * @return PDO|null
     */
    protected static function getDB(): ?PDO
    {
        static $db = null;

        if ($db === null) {
            $host = self::getRequiredEnv('DB_HOST');
            $name = self::getRequiredEnv('DB_NAME');
            $user = self::getRequiredEnv('DB_USER');
            $password = self::getRequiredEnv('DB_PASSWORD');

            $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8';
            $db = new PDO($dsn, $user, $password);

            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $db;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private static function getRequiredEnv(string $name): string
    {
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }

        if (isset($_ENV[$name]) && $_ENV[$name] !== '') {
            return $_ENV[$name];
        }

        throw new RuntimeException(
            'Missing required env var ' . $name
        );
    }
}
