<?php

declare(strict_types=1);

use CnA\Migrations\Blueprint;
use CnA\Migrations\Migration;
use CnA\Migrations\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->decimal('total_amount', 10, 2);
            $table->string('status', 50);
            $table->foreignKey('user_id', 'users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

