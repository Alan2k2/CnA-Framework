<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use CnA\Testing\TestCase;

class OrmTest extends TestCase
{
    public function setUp(): void
    {
        $this->app = $this->createApplication();
        $this->app->bootstrap();

        // Ensure database tables exist
        /** @var \CnA\Migrations\Migrator $migrator */
        $migrator = new \CnA\Migrations\Migrator();
        $migrator->run($this->app->basePath('database/migrations'));
    }

    public function testModelCrud(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
        ]);

        $this->assertTrue($user->id !== null);
        $this->assertEquals('John Doe', $user->name);

        // Update
        $user->name = 'Johnathan Doe';
        $user->save();

        $fresh = User::find($user->id);
        $this->assertEquals('Johnathan Doe', $fresh->name);

        // Delete
        $user->delete();
        $this->assertTrue(User::find($user->id) === null);
    }

    public function testRelationshipsAndEagerLoading(): void
    {
        $user = User::create([
            'name' => 'Sarah Connor',
            'email' => 'sarah@example.com',
            'password' => 'secret',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => 150.00,
            'status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Laser Rifle',
            'quantity' => 1,
            'unit_price' => 150.00,
        ]);

        // Eager load order with user and items
        $orders = Order::with(['user', 'items'])->where('id', $order->id)->get();

        $this->assertCount(1, $orders);
        $retrieved = $orders[0];

        $this->assertTrue($retrieved->relationLoaded('user'));
        $this->assertTrue($retrieved->relationLoaded('items'));
        $this->assertEquals('Sarah Connor', $retrieved->user->name);
        $this->assertCount(1, $retrieved->items);
        $this->assertEquals('Laser Rifle', $retrieved->items[0]->product_name);
    }

    public function testBelongsToManyRelationship(): void
    {
        $conn = $this->app->get(\CnA\Contracts\Database\ConnectionInterface::class);
        $conn->statement("CREATE TABLE IF NOT EXISTS role_user (user_id INTEGER, role_id INTEGER)");

        $user = new User(['id' => 10]);
        $user->syncOriginal();

        $relation = $user->belongsToMany(
            User::class,
            'role_user',
            'user_id',
            'role_id'
        );

        $this->assertEquals('users', $relation->getRelatedTable());
    }
}
