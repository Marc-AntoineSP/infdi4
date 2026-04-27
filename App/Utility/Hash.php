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
        $charset = $_ENV['SALT_CHARSET'];
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
