<?php

namespace Core;

use RuntimeException;

class RenderException extends RuntimeException
{
    public function __construct(string $message = 'Twig render exception.', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}
