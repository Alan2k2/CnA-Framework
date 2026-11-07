<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use CnA\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'Demo Customer',
            'email' => 'customer@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
            'api_token' => hash('sha256', 'demo-api-token-12345'),
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => 249.98,
            'status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Cloud Architecture Blueprint',
            'quantity' => 1,
            'unit_price' => 149.99,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'API Security Toolkit',
            'quantity' => 1,
            'unit_price' => 99.99,
        ]);
    }
}

