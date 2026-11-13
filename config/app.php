<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'CnA Framework'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'key' => env('APP_KEY', ''),

    'providers' => [
        \CnA\Core\Providers\LoggingServiceProvider::class,
        \CnA\Core\Providers\DatabaseServiceProvider::class,
        \CnA\Core\Providers\CacheServiceProvider::class,
        \CnA\Core\Providers\QueueServiceProvider::class,
        \CnA\Core\Providers\AiServiceProvider::class,
        \CnA\Core\Providers\MonitoringServiceProvider::class,
        \CnA\Core\Providers\RoutingServiceProvider::class,
        \CnA\View\ViewServiceProvider::class,
        \CnA\Frontend\FrontendServiceProvider::class,
    ],
];

