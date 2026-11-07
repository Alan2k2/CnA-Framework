<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use CnA\Contracts\Exceptions\LazyLoadingViolationException;
use CnA\Contracts\Exceptions\QueryBudgetExceededException;
use CnA\Monitoring\QueryObserver;
use CnA\Orm\Model;
use CnA\Testing\TestCase;

class NPlusOneDetectionTest extends TestCase
{
    public function setUp(): void
    {
        $this->app = $this->createApplication();
        $this->app->bootstrap();

        $migrator = new \CnA\Migrations\Migrator();
        $migrator->run($this->app->basePath('database/migrations'));
    }

    public function testStrictModeThrowsExceptionOnLazyLoading(): void
    {
        $user = User::create([
            'name' => 'John Connor',
            'email' => 'john.connor@example.com',
            'password' => 'secret',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => 99.99,
            'status' => 'pending',
        ]);

        // Enable strict lazy loading prevention
        Model::preventLazyLoading(true);

        // Fetch without eager loading
        $loadedOrder = Order::find($order->id);

        $caught = false;
        try {
            // Attempt dynamic lazy-loading access
            $items = $loadedOrder->items;
        } catch (LazyLoadingViolationException $e) {
            $caught = true;
            $this->assertEquals(Order::class, $e->model);
            $this->assertEquals('items', $e->relation);
            $this->assertTrue(str_contains($e->getMessage(), 'lazy loading is disabled in strict mode'));
        }

        $this->assertTrue($caught, 'Strict mode must throw LazyLoadingViolationException on dynamic lazy loading');
    }

    public function testEagerLoadingBypassesLazyLoadingViolation(): void
    {
        Model::preventLazyLoading(true);

        $user = User::create([
            'name' => 'Kyle Reese',
            'email' => 'kyle@example.com',
            'password' => 'secret',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => 45.00,
            'status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Trench Coat',
            'quantity' => 1,
            'unit_price' => 45.00,
        ]);

        // With eager loading
        $orders = Order::with(['items', 'user'])->where('id', $order->id)->get();
        $this->assertCount(1, $orders);

        // Accessing pre-loaded relations must succeed without exception
        $this->assertEquals('Trench Coat', $orders[0]->items[0]->product_name);
        $this->assertEquals('Kyle Reese', $orders[0]->user->name);
    }

    public function testQueryBudgetEnforcement(): void
    {
        /** @var QueryObserver $observer */
        $observer = $this->app->get(QueryObserver::class);
        $observer->reset();
        $observer->setQueryBudget(2); // Allow maximum 2 queries

        $conn = $this->app->get(\CnA\Contracts\Database\ConnectionInterface::class);

        // Query 1
        $conn->table('users')->first();
        // Query 2
        $conn->table('orders')->first();

        $budgetExceeded = false;
        try {
            // Query 3 should trigger violation
            $conn->table('order_items')->first();
        } catch (QueryBudgetExceededException $e) {
            $budgetExceeded = true;
            $this->assertEquals(2, $e->budget);
            $this->assertEquals(3, $e->actualCount);
        } finally {
            $observer->setQueryBudget(null);
        }

        $this->assertTrue($budgetExceeded, 'Query budget limit must be enforced');
    }
}

