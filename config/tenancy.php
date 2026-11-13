<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Resolution Configuration
    |--------------------------------------------------------------------------
    | Strategy: 'subdomain', 'header', or 'path'
    */

    'strategy' => env('TENANCY_STRATEGY', 'header'),

    'header' => 'X-Tenant-ID',

    'domain_suffix' => env('TENANCY_DOMAIN_SUFFIX', '.app.localhost'),

    'tenants' => [
        // 'tenant-1' => ['name' => 'Demo Tenant', 'db' => 'tenant_1'],
    ],
];

