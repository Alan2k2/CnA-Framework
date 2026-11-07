<?php

declare(strict_types=1);

// Web entrypoint
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    $cna = file_exists(__DIR__ . '/../cna') ? __DIR__ . '/../cna' : __DIR__ . '/../../cna';
    require_once $cna;
}

$bootstrap = file_exists(__DIR__ . '/../skeleton/bootstrap/app.php')
    ? __DIR__ . '/../skeleton/bootstrap/app.php'
    : (file_exists(__DIR__ . '/../bootstrap/app.php') ? __DIR__ . '/../bootstrap/app.php' : __DIR__ . '/../../skeleton/bootstrap/app.php');

$app = require $bootstrap;
$app->bootstrap();

$kernel = $app->get(\CnA\Http\Kernel::class);

$request = \CnA\Http\Request::createFromGlobals();

$response = $kernel->handle($request);

$response->send();

$kernel->terminate($request, $response);
