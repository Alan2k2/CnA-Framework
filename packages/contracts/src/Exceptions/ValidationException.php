<?php

declare(strict_types=1);

namespace CnA\Contracts\Exceptions;

class ValidationException extends CnAException
{
    public function __construct(
        protected array $errors = [],
        string $message = "The given data was invalid.",
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}

