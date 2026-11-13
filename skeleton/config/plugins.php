<?php

declare(strict_types=1);

return [
    'path' => env('PLUGINS_PATH', dirname(__DIR__) . '/plugins'),

    'auto_discover' => env('PLUGINS_AUTO_DISCOVER', true),
];
