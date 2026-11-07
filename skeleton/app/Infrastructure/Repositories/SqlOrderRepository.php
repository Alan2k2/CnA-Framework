<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Order;
use App\Domain\Repositories\OrderRepositoryInterface;
use CnA\Contracts\Database\ConnectionInterface;

class SqlOrderRepository implements OrderRepositoryInterface
{
    public function __construct(protected ConnectionInterface $connection)
    {
    }

    public function findById(int $id): ?Order
    {
        $row = $this->connection->table('orders')->where('id', $id)->first();
        if (!$row) {
            return null;
        }

        return new Order(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            totalAmount: (float) $row['total_amount'],
            status: (string) $row['status'],
            createdAt: $row['created_at'] ?? null
        );
    }

    public function save(Order $order): Order
    {
        if ($order->id === null) {
            $id = $this->connection->table('orders')->insert([
                'user_id' => $order->userId,
                'total_amount' => $order->totalAmount,
                'status' => $order->status,
                'created_at' => $order->createdAt ?? date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $order->id = (int) $id;
        } else {
            $this->connection->table('orders')->where('id', $order->id)->update([
                'status' => $order->status,
                'total_amount' => $order->totalAmount,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $order;
    }

    public function getByUserId(int $userId): array
    {
        $rows = $this->connection->table('orders')->where('user_id', $userId)->get();
        return array_map(fn($row) => new Order(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            totalAmount: (float) $row['total_amount'],
            status: (string) $row['status'],
            createdAt: $row['created_at'] ?? null
        ), $rows);
    }
}

