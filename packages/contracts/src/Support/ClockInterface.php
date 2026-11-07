<?php

declare(strict_types=1);

namespace CnA\Contracts\Support;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}

