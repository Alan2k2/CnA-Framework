<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/autoload.php';

use CnA\Container\Container;
use CnA\Routing\Router;
use CnA\Http\Request;
use CnA\Database\Connection;
use CnA\QueryBuilder\QueryBuilder;
use App\Models\User;

echo "=== CnA Framework Micro-Benchmarks ===\n\n";

$results = [];

// 1. Container Resolution Benchmark
$container = new Container();
$container->bind('sample.service', fn() => new stdClass());

$iterations = 50_000;
$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $obj = $container->get('sample.service');
}
$durationNs = hrtime(true) - $start;
$durationMs = $durationNs / 1_000_000;
$opsPerSec = ($iterations / ($durationNs / 1_000_000_000));

$results['container_resolution'] = [
    'iterations' => $iterations,
    'total_ms' => round($durationMs, 2),
    'ops_per_second' => round($opsPerSec),
    'avg_microseconds_per_op' => round(($durationMs * 1000) / $iterations, 4),
];
echo sprintf(
    "Container Resolution: %d ops in %.2f ms (%.0f ops/sec, %.4f µs/op)\n",
    $iterations,
    $durationMs,
    $opsPerSec,
    ($durationMs * 1000) / $iterations
);

// 2. Router Match Benchmark
$router = new Router($container);
$router->get('/api/v1/health', fn() => 'ok')->name('health');
$router->get('/api/v1/orders', fn() => 'orders')->name('orders.index');
$router->post('/api/v1/orders', fn() => 'create')->name('orders.store');
$router->get('/api/v1/orders/{id}', fn($id) => $id)->name('orders.show');

$request = new Request('GET', '/api/v1/orders/42');
$iterations = 50_000;
$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $response = $router->dispatch($request);
}
$durationNs = hrtime(true) - $start;
$durationMs = $durationNs / 1_000_000;
$opsPerSec = ($iterations / ($durationNs / 1_000_000_000));

$results['router_dispatch'] = [
    'iterations' => $iterations,
    'total_ms' => round($durationMs, 2),
    'ops_per_second' => round($opsPerSec),
    'avg_microseconds_per_op' => round(($durationMs * 1000) / $iterations, 4),
];
echo sprintf(
    "Router Match:         %d ops in %.2f ms (%.0f ops/sec, %.4f µs/op)\n",
    $iterations,
    $durationMs,
    $opsPerSec,
    ($durationMs * 1000) / $iterations
);

// 3. Query Builder SQL Compilation Benchmark
$connection = new Connection('sqlite', new PDO('sqlite::memory:'), new \CnA\Database\Dialects\SqliteDialect());
$iterations = 25_000;
$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $qb = new QueryBuilder($connection);
    $qb->table('orders')
        ->select(['id', 'user_id', 'total_amount', 'status'])
        ->where('status', '=', 'completed')
        ->where('total_amount', '>', 100)
        ->orderBy('id', 'DESC')
        ->limit(10);
    $sql = $qb->toSql();
}
$durationNs = hrtime(true) - $start;
$durationMs = $durationNs / 1_000_000;
$opsPerSec = ($iterations / ($durationNs / 1_000_000_000));

$results['query_builder_compilation'] = [
    'iterations' => $iterations,
    'total_ms' => round($durationMs, 2),
    'ops_per_second' => round($opsPerSec),
    'avg_microseconds_per_op' => round(($durationMs * 1000) / $iterations, 4),
];
echo sprintf(
    "QueryBuilder SQL:     %d ops in %.2f ms (%.0f ops/sec, %.4f µs/op)\n",
    $iterations,
    $durationMs,
    $opsPerSec,
    ($durationMs * 1000) / $iterations
);

// 4. Model Hydration Benchmark
$iterations = 25_000;
$row = [
    'id' => 101,
    'name' => 'Benchmark User',
    'email' => 'alanshaju26@icloud.com',
    'created_at' => '2026-11-07 12:00:00',
    'updated_at' => '2026-11-07 12:00:00',
];
$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $user = User::newFromBuilder($row);
}
$durationNs = hrtime(true) - $start;
$durationMs = $durationNs / 1_000_000;
$opsPerSec = ($iterations / ($durationNs / 1_000_000_000));

$results['model_hydration'] = [
    'iterations' => $iterations,
    'total_ms' => round($durationMs, 2),
    'ops_per_second' => round($opsPerSec),
    'avg_microseconds_per_op' => round(($durationMs * 1000) / $iterations, 4),
];
echo sprintf(
    "Model Hydration:      %d ops in %.2f ms (%.0f ops/sec, %.4f µs/op)\n\n",
    $iterations,
    $durationMs,
    $opsPerSec,
    ($durationMs * 1000) / $iterations
);

$baseline = [
    'framework' => 'CnA Framework',
    'version' => '1.0.0-dev',
    'php_version' => PHP_VERSION,
    'os' => PHP_OS,
    'timestamp' => date('c'),
    'metrics' => $results,
];

file_put_contents(__DIR__ . '/baseline.json', json_encode($baseline, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Saved baseline metrics to benchmarks/baseline.json\n";
