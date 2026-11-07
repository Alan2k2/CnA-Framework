<?php

declare(strict_types=1);

return [
    'default' => env('AI_DEFAULT_PROVIDER', 'fake'),

    'allowed_models' => [
        'fake-model-v1',
        'gpt-4o',
        'gpt-4o-mini',
        'gemini-1.5-pro',
        'gemini-1.5-flash',
    ],

    'quotas' => [
        'default_tenant' => (int) env('AI_TOKEN_BUDGET', 100000),
    ],
];
