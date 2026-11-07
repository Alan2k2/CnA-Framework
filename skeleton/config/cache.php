<?php

declare(strict_types=1);

return [
    'default' => env('CACHE_DRIVER', 'array'),

    'stores' => [
        'array' => [
            'driver' => 'array',
        ],

        'file' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../storage/cache',
        ],
    ],
];
