<?php

namespace App\AI\Exceptions;

use RuntimeException;

class AiNotConnectedException extends RuntimeException
{
    public function __construct(string $message = 'No AI account is connected.')
    {
        parent::__construct($message);
    }
}
