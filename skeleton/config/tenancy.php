<?php

declare(strict_types=1);

return [
    'strategy' => env('TENANCY_STRATEGY', 'header'),

    'header' => 'X-Tenant-ID',

    'domain_suffix' => env('TENANCY_DOMAIN_SUFFIX', '.app.localhost'),

    'tenants' => [],
];
