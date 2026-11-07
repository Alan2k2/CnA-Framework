<?php

declare(strict_types=1);

namespace CnA\Contracts\Exceptions;

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

