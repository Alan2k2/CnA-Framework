<?php

declare(strict_types=1);

return [
    'default' => env('DB_CONNECTION', 'sqlite'),

    'query_budget' => env('DB_QUERY_BUDGET', null),

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', __DIR__ . '/../storage/database.sqlite'),
            'prefix' => '',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'cna_app'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'options' => [
                // Enforce TLS certificate and hostname verification against MariaDB Connector/C MITM (CVE-2026-107819)
                \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => (bool) env('DB_SSL_VERIFY_SERVER_CERT', true),
            ],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'cna_app'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
        ],
    ],
];

