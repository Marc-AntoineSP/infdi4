<?php

namespace App\Utility;

use LogicException;
use Random\RandomException;
use RuntimeException;

/**
 * Hash:
 */
class Hash {

    /**
     * Génère et retourne un hash
     */
    public static function generate($string, $salt = ""): string
    {
        return(hash("sha256", $string . $salt));
    }

    /**
     * Génère et retourne un salt
     */
    public static function generateSalt($length): string
    {
        $salt = "";
        $charset = getenv('SALT_CHARSET');
        if (($charset === false || $charset === '') && isset($_ENV['SALT_CHARSET'])) {
            $charset = $_ENV['SALT_CHARSET'];
        }

        if ($charset === false || $charset === '') {
            throw new RuntimeException(
                'Missing required env var SALT_CHARSET. Start services with `make up` (uses .env.dev).'
            );
        }

        $charsetLength = strlen($charset);

        if ($charsetLength === 0) {
            throw new RuntimeException('SALT_CHARSET must not be empty');
        }

        for ($i = 0; $i < $length; $i++) {
            try {
                $salt .= $charset[random_int(0, $charsetLength - 1)];
            } catch (RandomException $e) {
                throw new LogicException("Unable to generate salt: " . $e->getMessage());
            }
        }
        return $salt;
    }

    /**
     * Génère et retourne un UID
     */
    public static function generateUnique(): string
    {
        return(self::generate(uniqid(more_entropy: true)));
    }

}
