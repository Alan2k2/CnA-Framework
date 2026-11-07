<?php

declare(strict_types=1);

namespace CnA\Middleware;

use CnA\Contracts\Http\MiddlewareInterface;
use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = $next($request);

        // Allow custom CSP via config or environment
        $cspPolicy = '';
        if (function_exists('config')) {
            $cspPolicy = (string) config('security.csp', '');
        }
        if (empty($cspPolicy) && function_exists('env')) {
            $cspPolicy = (string) env('CSP_POLICY', '');
        }

        // Standard OWASP ASVS compliant baseline that supports UI assets, Google Fonts, and modern web apps
        if (empty($cspPolicy)) {
            $cspPolicy = "default-src 'self' data:; " .
                         "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
                         "font-src 'self' https://fonts.gstatic.com data:; " .
                         "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
                         "img-src 'self' data: https:;";
        }

        return $response
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-XSS-Protection', '0')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Content-Security-Policy', $cspPolicy)
            ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
