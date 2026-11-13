<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'mode' => env('APP_ENV') === 'production' ? 'production' : 'development',
    'dev_server' => [
        'url' => env('VITE_DEV_SERVER_URL', 'http://127.0.0.1:5173'),
        'allowed_origins' => [
            'http://127.0.0.1:5173',
            'http://localhost:5173',
            'http://127.0.0.1:8000',
            'http://localhost:8000',
        ],
        'timeout' => 0.05,
    ],
    'build' => [
        'directory' => dirname(__DIR__, 2) . '/public/build',
        'manifest' => dirname(__DIR__, 2) . '/public/build/.vite/manifest.json',
        'public_path' => '/build/',
    ],
    'entry_points' => [
        'app' => 'resources/js/app.ts',
        'styles' => 'resources/css/app.css',
        'dashboard' => 'resources/modules/dashboard/index.ts',
    ],
];

