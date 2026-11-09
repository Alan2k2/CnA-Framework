<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Contracts\Exceptions\SecurityException;
use CnA\Http\Request;
use CnA\Http\Response;
use CnA\Routing\Router;
use CnA\Security\SsrfValidator;
use CnA\Storage\Disks\LocalDisk;
use CnA\Testing\TestCase;

class SecurityAdvisoryRegressionTest extends TestCase
{
    /**
     * GHSA-4qpf-57w4-m3wc: IPv4-mapped and compatible IPv6 addresses bypass
     */
    public function testSsrfRejectsIpv4MappedIpv6Addresses(): void
    {
        $validator = new SsrfValidator();

        // Loopback mapped IPv6
        $this->assertTrue($validator->isPrivateOrReservedIp('::ffff:127.0.0.1'));
        $this->assertTrue($validator->isPrivateOrReservedIp('::127.0.0.1'));
        $this->assertTrue($validator->isPrivateOrReservedIp('::ffff:7f00:1'));

        // Cloud metadata mapped IPv6
        $this->assertTrue($validator->isPrivateOrReservedIp('::ffff:169.254.169.254'));

        // Private RFC1918 mapped IPv6
        $this->assertTrue($validator->isPrivateOrReservedIp('::ffff:10.0.0.1'));
        $this->assertTrue($validator->isPrivateOrReservedIp('::ffff:192.168.1.1'));

        // IPv6 Loopback and Unspecified
        $this->assertTrue($validator->isPrivateOrReservedIp('::1'));
        $this->assertTrue($validator->isPrivateOrReservedIp('::'));
    }

    /**
     * GHSA-c7hm-wqwp-f9v8: DNS Rebinding defense via IP Pinning
     */
    public function testSsrfProvidesDnsPinningToPreventRebinding(): void
    {
        $validator = new SsrfValidator();

        // Valid public destination IP literal (deterministic offline test)
        $pinned = $validator->validateAndPin('https://93.184.216.34:8443/api/webhook');

        $this->assertEquals('https://93.184.216.34:8443/api/webhook', $pinned['url']);
        $this->assertEquals('93.184.216.34', $pinned['host']);
        $this->assertEquals(8443, $pinned['port']);
        $this->assertEquals('93.184.216.34', $pinned['pinned_ip']);
        $this->assertFalse($validator->isPrivateOrReservedIp($pinned['pinned_ip']));
    }

    /**
     * CVE-2026-69246: Noncanonical host and authority confusion
     */
    public function testSsrfRejectsEmbeddedCredentialsAndControlChars(): void
    {
        $validator = new SsrfValidator();

        $caught = false;
        try {
            $validator->validateUrl('http://admin:secret@93.184.216.34/data');
        } catch (SecurityException) {
            $caught = true;
        }
        $this->assertTrue($caught, 'Validator should reject URLs with embedded userinfo.');

        // Test CRLF in URL
        $crlfCaught = false;
        try {
            $validator->validateUrl("http://93.184.216.34/test\r\nX-Injected: 1");
        } catch (SecurityException) {
            $crlfCaught = true;
        }
        $this->assertTrue($crlfCaught, 'Validator should reject URLs with CRLF.');
    }

    /**
     * CVE-2026-55766: CRLF Injection in PSR-7 / HTTP Start-Line & Headers
     */
    public function testResponseRejectsCrlfInjectionInHeaders(): void
    {
        $response = new Response('OK', 200);

        // Test CRLF in header name
        $nameCaught = false;
        try {
            $response->withHeader("X-Injected\r\nSet-Cookie: evil=1", 'test');
        } catch (\InvalidArgumentException) {
            $nameCaught = true;
        }
        $this->assertTrue($nameCaught, 'Response should reject CRLF in header names.');

        // Test CRLF in header value
        $valCaught = false;
        try {
            $response->withHeader('X-Custom', "value\r\nSet-Cookie: session=hijacked");
        } catch (\InvalidArgumentException) {
            $valCaught = true;
        }
        $this->assertTrue($valCaught, 'Response should reject CRLF in header values.');

        // Test CRLF in status reason phrase
        $phraseCaught = false;
        try {
            $response->withStatus(200, "OK\r\nLocation: http://evil.com");
        } catch (\InvalidArgumentException) {
            $phraseCaught = true;
        }
        $this->assertTrue($phraseCaught, 'Response should reject CRLF in status phrases.');
    }

    /**
     * CVE-2026-118516: Host/path collision and path traversal bypass in Request
     */
    public function testRequestCanonicalizesPathAndPreventsHostConfusion(): void
    {
        // Traversal resolution
        $req1 = new Request('GET', '/api/v1/../orders');
        $this->assertEquals('/api/orders', $req1->path());

        // Leading consecutive slashes
        $req2 = new Request('GET', '//admin/dashboard');
        $this->assertEquals('/admin/dashboard', $req2->path());

        // Triple slashes
        $req3 = new Request('GET', '///settings');
        $this->assertEquals('/settings', $req3->path());
    }

    /**
     * CVE-2026-118514: HEAD requests must execute identical authorization/action but have empty body
     */
    public function testHeadRequestExecutesActionAndEmptiesBody(): void
    {
        $router = new Router();
        $router->get('/test-route', function () {
            return new Response('{"secret":"content"}', 200, ['Content-Type' => 'application/json']);
        });

        // Test GET
        $getRequest = new Request('GET', '/test-route');
        $getResponse = $router->dispatch($getRequest);
        $this->assertEquals(200, $getResponse->getStatusCode());
        $this->assertEquals('{"secret":"content"}', $getResponse->getBody());

        // Test HEAD
        $headRequest = new Request('HEAD', '/test-route');
        $headResponse = $router->dispatch($headRequest);
        $this->assertEquals(200, $headResponse->getStatusCode());
        $this->assertEquals('', $headResponse->getBody(), 'HEAD response body must be strictly empty (RFC 9110).');
        $this->assertEquals(['application/json'], $headResponse->getHeader('content-type'));
    }

    /**
     * GHSA-6prc-h956-hxjq & CVE-2026-118515: Storage disk traversal and prefix boundary checks
     */
    public function testLocalStorageDiskRejectsTraversalAndNullBytes(): void
    {
        $tempDir = sys_get_temp_dir() . '/cna_sec_test_' . uniqid();
        $disk = new LocalDisk($tempDir);

        // Traversal attempt
        $traversalCaught = false;
        try {
            $disk->get('../../etc/passwd');
        } catch (SecurityException) {
            $traversalCaught = true;
        }
        $this->assertTrue($traversalCaught, 'Disk should reject traversal paths.');

        // Null byte attempt
        $nullCaught = false;
        try {
            $disk->get("file.txt\0.jpg");
        } catch (SecurityException) {
            $nullCaught = true;
        }
        $this->assertTrue($nullCaught, 'Disk should reject null bytes.');

        // Cleanup
        if (is_dir($tempDir)) {
            @rmdir($tempDir);
        }
    }
}
