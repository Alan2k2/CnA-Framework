<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Commands\CreateOrderCommand;
use App\Domain\Entities\Order;
use App\Domain\Repositories\OrderRepositoryInterface;
use CnA\Contracts\Database\TransactionManagerInterface;

final class CreateOrderService
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private TransactionManagerInterface $transactions
    ) {
    }

    public function execute(CreateOrderCommand $command): Order
    {
        return $this->transactions->run(function () use ($command) {
            $order = Order::create($command->userId, $command->totalAmount);
            return $this->orders->save($order);
        });
    }
}

