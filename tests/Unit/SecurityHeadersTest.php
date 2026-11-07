<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Http\Request;
use CnA\Http\Response;
use CnA\Middleware\SecurityHeadersMiddleware;
use CnA\Testing\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function testSecurityHeadersAndCspPolicy(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = new Request('GET', '/');

        $response = $middleware->process($request, function ($req) {
            return new Response('Hello World', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['DENY'], $response->getHeader('x-frame-options'));
        $this->assertEquals(['nosniff'], $response->getHeader('x-content-type-options'));
        $this->assertEquals(['0'], $response->getHeader('x-xss-protection'));
        $this->assertEquals(['strict-origin-when-cross-origin'], $response->getHeader('referrer-policy'));

        $csp = $response->getHeaderLine('content-security-policy');
        $this->assertTrue(str_contains($csp, "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com"));
        $this->assertTrue(str_contains($csp, "font-src 'self' https://fonts.gstatic.com data:"));
        $this->assertTrue(str_contains($csp, "script-src 'self' 'unsafe-inline' 'unsafe-eval'"));
    }
}

