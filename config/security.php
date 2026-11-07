<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP)
    |--------------------------------------------------------------------------
    |
    | Defines the allowed sources for scripts, styles, fonts, and images.
    | By default, supports self, inline scripts/styles, and Google Fonts.
    |
    */
    'csp' => env('CSP_POLICY', "default-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; script-src 'self' 'unsafe-inline' 'unsafe-eval'; img-src 'self' data: https:;"),

    /*
    |--------------------------------------------------------------------------
    | OWASP ASVS Headers
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '0',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    ],
];

