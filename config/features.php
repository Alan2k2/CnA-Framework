<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags Configuration
    |--------------------------------------------------------------------------
    | Define baseline features, percentage rollouts, or environment overrides.
    */

    'flags' => [
        'new_dashboard' => env('FEATURE_NEW_DASHBOARD', false),
        'beta_api' => env('FEATURE_BETA_API', false),
    ],
];
