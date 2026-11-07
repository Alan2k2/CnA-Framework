<?php

declare(strict_types=1);

use App\Domain\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Repositories\SqlOrderRepository;
use CnA\Core\Application;

$basePath = file_exists(dirname(__DIR__, 2) . '/composer.json') ? dirname(__DIR__, 2) : dirname(__DIR__);
$app = new Application($basePath);

// Bind Application Use Cases and Domain Repositories
$app->bind(OrderRepositoryInterface::class, SqlOrderRepository::class);

return $app;
