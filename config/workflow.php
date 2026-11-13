<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CnA Workflow Engine Configuration
    |--------------------------------------------------------------------------
    */

    'default' => env('WORKFLOW_ENGINE', 'sync'),

    'engines' => [
        'sync' => [
            'history' => true,
            'max_steps' => 100,
        ],
    ],
];
