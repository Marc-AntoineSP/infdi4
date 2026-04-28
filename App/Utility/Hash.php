<?php

namespace App\Utility;

use InvalidArgumentException;
use Random\RandomException;
use UnexpectedValueException;

/**
 * Hash:
 */
class Hash {

    /**
     * Génère et retourne un hash
     */
    public static function generate($string, $salt = ""): string
    {
        return hash("sha256", $string . $salt);
    }

    /**
     * Génère et retourne un salt
     */
    public static function generateSalt(int $length): string
    {
        if ($length <= 0) {
            throw new InvalidArgumentException('Salt length must be a positive integer.');
        }

        $salt = "";
        $charset = getenv('SALT_CHARSET');
        if (($charset === false || $charset === '') && isset($_ENV['SALT_CHARSET'])) {
            $charset = $_ENV['SALT_CHARSET'];
        }

        if ($charset === false || $charset === '') {
            throw new UnexpectedValueException(
                'Missing required env var SALT_CHARSET in PHP environment.'
            );
        }

        $charsetLength = strlen($charset);

        if ($charsetLength < 2) {
            throw new UnexpectedValueException('SALT_CHARSET must contain at least 2 characters.');
        }

        for ($i = 0; $i < $length; $i++) {
            try {
                $salt .= $charset[random_int(0, $charsetLength - 1)];
            } catch (RandomException $e) {
                throw new UnexpectedValueException(
                    'Unable to generate cryptographically secure salt.',
                    0,
                    $e
                );
            }
        }
        return $salt;
    }

    /**
     * Génère et retourne un UID
     */
    public static function generateUnique(): string
    {
        return self::generate(uniqid(more_entropy: true));
    }

}
