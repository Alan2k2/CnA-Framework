<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Repositories\SqlOrderRepository;
use CnA\Testing\TestCase;

class HttpTest extends TestCase
{
    public function setUp(): void
    {
        $this->app = $this->createApplication();
        $this->app->bootstrap();

        $this->app->bind(OrderRepositoryInterface::class, SqlOrderRepository::class);

        $migrator = new \CnA\Migrations\Migrator();
        $migrator->run($this->app->basePath('database/migrations'));
    }

    public function testHealthCheckEndpoint(): void
    {
        $response = $this->get('/api/v1/health');

        $response->assertOk()
            ->assertJson(['status' => 'healthy']);

        // Check security headers
        $this->assertEquals('DENY', $response->response->getHeaderLine('X-Frame-Options'));
        $this->assertEquals('nosniff', $response->response->getHeaderLine('X-Content-Type-Options'));
    }

    public function testOrderCreationAndRetrieval(): void
    {
        // 1. Create order
        $createResponse = $this->post('/api/v1/orders', [
            'user_id' => 1,
            'total_amount' => 199.95,
        ]);

        $createResponse->assertCreated()
            ->assertJson([
                'message' => 'Order created successfully.',
            ]);

        $orderId = $createResponse->json('order.id');
        $this->assertTrue($orderId > 0);

        // 2. Fetch created order
        $showResponse = $this->get("/api/v1/orders/{$orderId}");
        $showResponse->assertOk()
            ->assertJson([
                'order' => [
                    'id' => $orderId,
                    'user_id' => 1,
                    'total_amount' => 199.95,
                    'status' => 'pending',
                ],
            ]);
    }

    public function testOrderCreationValidationFailure(): void
    {
        $response = $this->post('/api/v1/orders', [
            'user_id' => 'not-an-int',
        ]);

        $response->assertUnprocessable();
        $errors = $response->json('errors');
        $this->assertTrue(isset($errors['user_id']));
        $this->assertTrue(isset($errors['total_amount']));
    }

    public function testOrderNotFoundReturns404(): void
    {
        $response = $this->get('/api/v1/orders/999999');
        $response->assertNotFound();
    }
}

