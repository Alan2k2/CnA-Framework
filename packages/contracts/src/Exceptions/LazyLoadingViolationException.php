<?php

declare(strict_types=1);

namespace CnA\Contracts\Exceptions;

class LazyLoadingViolationException extends DatabaseException
{
    public function __construct(
        public readonly string $model,
        public readonly string $relation,
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if ($message === "") {
            $message = "Attempted to lazy load [{$relation}] on model [{$model}] but lazy loading is disabled in strict mode. Use eager loading `with(['{$relation}'])` instead.";
        }
        parent::__construct($message, $code, $previous);
    }
}

