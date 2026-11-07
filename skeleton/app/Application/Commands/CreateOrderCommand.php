<?php

declare(strict_types=1);

namespace App\Application\Commands;

class CreateOrderCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly float $totalAmount,
        public readonly array $items = []
    ) {
    }
}

