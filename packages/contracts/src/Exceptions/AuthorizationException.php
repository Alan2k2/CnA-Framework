<?php

declare(strict_types=1);

namespace CnA\Contracts\Exceptions;

class AuthorizationException extends CnAException
{
    public function __construct(string $message = "This action is unauthorized.", int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

