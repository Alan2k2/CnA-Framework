<?php

declare(strict_types=1);

return [
    'default' => [
        'guard' => 'token',
    ],

    'providers' => [
        'users' => [
            'model' => \App\Models\User::class,
        ],
    ],
];
