<?php

declare(strict_types=1);

namespace App\Utility;

class Helpers {
    public static function dd(mixed $object): void
    {
        var_dump($object);
        die(1);
    }
}