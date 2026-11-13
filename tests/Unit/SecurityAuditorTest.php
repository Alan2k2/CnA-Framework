<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Security\SecurityAuditor;
use CnA\Testing\TestCase;

class SecurityAuditorTest extends TestCase
{
    public function testSecurityAuditorFullReport(): void
    {
        $auditor = new SecurityAuditor();
        $audit = $auditor->runFullAudit();

        $this->assertTrue(isset($audit['status']));
        $this->assertTrue(isset($audit['summary']));
        $this->assertTrue(isset($audit['dependencies']));
        $this->assertTrue(isset($audit['permissions']));
        $this->assertTrue(isset($audit['headers']));

        $json = $auditor->toJson();
        $this->assertTrue(str_contains($json, '"status":'));
        $this->assertTrue(str_contains($json, '"dependencies":'));
    }

    public function testSecurityHeadersDetection(): void
    {
        $auditor = new SecurityAuditor();

        $headers = [
            'Content-Security-Policy' => "default-src 'self'",
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=()',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        ];

        $result = $auditor->auditHeaders($headers);
        $this->assertEquals('PASS', $result['status']);
    }
}
