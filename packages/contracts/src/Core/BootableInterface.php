<?php

declare(strict_types=1);

namespace CnA\Contracts\Core;

interface BootableInterface
{
    public function boot(): void;
}

