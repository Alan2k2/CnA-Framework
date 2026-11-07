<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Order
{
    public function __construct(
        public ?int $id,
        public int $userId,
        public float $totalAmount,
        public string $status = 'pending',
        public ?string $createdAt = null
    ) {
    }

    public static function create(int $userId, float $totalAmount): static
    {
        return new static(
            id: null,
            userId: $userId,
            totalAmount: $totalAmount,
            status: 'pending',
            createdAt: date('Y-m-d H:i:s')
        );
    }
}

