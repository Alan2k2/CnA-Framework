<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Plugins Directory and Discovery Configuration
    |--------------------------------------------------------------------------
    */

    'path' => env('PLUGINS_PATH', dirname(__DIR__) . '/plugins'),

    'auto_discover' => env('PLUGINS_AUTO_DISCOVER', true),
];

