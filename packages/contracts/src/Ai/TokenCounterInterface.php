<?php

declare(strict_types=1);

namespace CnA\Contracts\Ai;

interface TokenCounterInterface
{
    public function count(string $text, ?string $model = null): int;
}

