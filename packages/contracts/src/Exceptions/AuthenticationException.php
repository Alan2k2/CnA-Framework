<?php

declare(strict_types=1);

namespace CnA\Contracts\Exceptions;

class AuthenticationException extends CnAException
{
    public function __construct(string $message = "Unauthenticated.", int $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

