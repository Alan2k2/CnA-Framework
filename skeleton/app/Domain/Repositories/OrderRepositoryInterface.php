<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Order;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;

    public function save(Order $order): Order;

    public function getByUserId(int $userId): array;
}

