<?php

declare(strict_types=1);

namespace CnA\Contracts\Exceptions;

class ContainerException extends CnAException {}
class NotFoundException extends CnAException {}

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

class AuthenticationException extends CnAException
{
    public function __construct(string $message = "Unauthenticated.", int $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class AuthorizationException extends CnAException
{
    public function __construct(string $message = "This action is unauthorized.", int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class DatabaseException extends CnAException {}

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

class QueryBudgetExceededException extends DatabaseException
{
    public function __construct(
        public readonly int $budget,
        public readonly int $actualCount,
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if ($message === "") {
            $message = "Query budget of {$budget} queries exceeded! Executed {$actualCount} queries.";
        }
        parent::__construct($message, $code, $previous);
    }
}

class SecurityException extends CnAException {}

