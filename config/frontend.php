<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CnA Frontend Engine Master Switch
    |--------------------------------------------------------------------------
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Operating Mode: 'development' or 'production'
    |--------------------------------------------------------------------------
    | In development mode, Vite's loopback HMR server is queried. In production,
    | assets and modulepreloads are resolved from the compiled manifest.
    */
    'mode' => env('APP_ENV') === 'production' ? 'production' : 'development',

    /*
    |--------------------------------------------------------------------------
    | Vite Development Server Settings
    |--------------------------------------------------------------------------
    | Must strictly bind to loopback (127.0.0.1 or localhost) for security.
    */
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

    /*
    |--------------------------------------------------------------------------
    | Production Build & Manifest Paths
    |--------------------------------------------------------------------------
    */
    'build' => [
        'directory' => dirname(__DIR__) . '/public/build',
        'manifest' => dirname(__DIR__) . '/public/build/.vite/manifest.json',
        'public_path' => '/build/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Registered Entry Points
    |--------------------------------------------------------------------------
    | Map stable logical names to source entry files.
    */
    'entry_points' => [
        'app' => 'resources/js/app.ts',
        'styles' => 'resources/css/app.css',
        'dashboard' => 'resources/modules/dashboard/index.ts',
    ],
];
