<?php

namespace Core;

use RuntimeException;

class RouteNotFoundException extends RuntimeException
{
    public function __construct(string $message = 'No route matched.', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}
