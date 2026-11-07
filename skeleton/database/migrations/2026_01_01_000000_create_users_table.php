<?php

declare(strict_types=1);

use CnA\Migrations\Blueprint;
use CnA\Migrations\Migration;
use CnA\Migrations\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('api_token', 255, true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

