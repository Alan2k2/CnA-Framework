<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use App\Domain\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Repositories\SqlOrderRepository;
use CnA\Core\Application;

$app = new Application(dirname(__DIR__));

// Bind Application Use Cases and Domain Repositories
$app->bind(OrderRepositoryInterface::class, SqlOrderRepository::class);

return $app;

