<?php

declare(strict_types=1);

use CnA\Migrations\Blueprint;
use CnA\Migrations\Migration;
use CnA\Migrations\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue');
            $table->text('payload');
            $table->integer('attempts', false, 0);
            $table->integer('reserved_at', true);
            $table->integer('available_at', false);
            $table->integer('created_at', false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};

