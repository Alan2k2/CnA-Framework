<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Commands\CreateOrderCommand;
use App\Application\Services\CreateOrderService;
use App\Domain\Repositories\OrderRepositoryInterface;
use App\Models\Order;
use CnA\Contracts\Exceptions\NotFoundException;
use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;
use CnA\Http\JsonResponse;
use CnA\Validation\Validator;

class OrderController
{
    public function __construct(
        private CreateOrderService $createOrderService,
        private OrderRepositoryInterface $orders
    ) {
    }

    public function index(RequestInterface $request): ResponseInterface
    {
        // Demonstrate eager loading to prevent N+1 queries
        $orders = Order::with(['items', 'user'])->get();
        return new JsonResponse(['data' => $orders]);
    }

    public function store(RequestInterface $request): ResponseInterface
    {
        $validated = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'total_amount' => 'required|numeric|min:0.01',
        ])->validate();

        $command = new CreateOrderCommand(
            userId: (int) $validated['user_id'],
            totalAmount: (float) $validated['total_amount']
        );

        $order = $this->createOrderService->execute($command);

        return new JsonResponse([
            'message' => 'Order created successfully.',
            'order' => [
                'id' => $order->id,
                'user_id' => $order->userId,
                'total_amount' => $order->totalAmount,
                'status' => $order->status,
                'created_at' => $order->createdAt,
            ],
        ], 201);
    }

    public function show(RequestInterface $request, string|int $id): ResponseInterface
    {
        $order = $this->orders->findById((int) $id);
        if ($order === null) {
            throw new NotFoundException("Order [{$id}] not found.");
        }

        return new JsonResponse([
            'order' => [
                'id' => $order->id,
                'user_id' => $order->userId,
                'total_amount' => $order->totalAmount,
                'status' => $order->status,
            ],
        ]);
    }
}

