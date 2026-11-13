<?php

declare(strict_types=1);

namespace CnA\Security;

class SecurityAuditor
{
    public function __construct(protected ?string $basePath = null)
    {
        $this->basePath = $basePath ?? (defined('CNA_ROOT') ? CNA_ROOT : getcwd());
    }

    /**
     * Audit Composer dependencies and lockfile integrity.
     *
     * @return array{status: string, items: array<array{name: string, status: string, details: string}>}
     */
    public function auditDependencies(): array
    {
        $items = [];
        $composerJsonPath = $this->basePath . '/composer.json';
        $composerLockPath = $this->basePath . '/composer.lock';

        if (!file_exists($composerJsonPath)) {
            return [
                'status' => 'FAIL',
                'items' => [
                    ['name' => 'composer.json', 'status' => 'FAIL', 'details' => 'composer.json not found in root']
                ]
            ];
        }

        $composerData = json_decode((string) file_get_contents($composerJsonPath), true) ?? [];
        $require = $composerData['require'] ?? [];

        // Check PHP constraint
        $phpReq = $require['php'] ?? null;
        if ($phpReq && str_contains($phpReq, '8.4')) {
            $items[] = ['name' => 'PHP Baseline Constraint', 'status' => 'PASS', 'details' => "Constraint: $phpReq"];
        } else {
            $items[] = ['name' => 'PHP Baseline Constraint', 'status' => 'WARN', 'details' => "PHP requirement is '$phpReq' (Recommended: >=8.4)"];
        }

        // Check lock file
        if (file_exists($composerLockPath)) {
            $items[] = ['name' => 'Composer Lockfile', 'status' => 'PASS', 'details' => 'composer.lock present and tracked'];
        } else {
            $items[] = ['name' => 'Composer Lockfile', 'status' => 'WARN', 'details' => 'composer.lock missing; dependencies are unpinned'];
        }

        $overall = array_reduce($items, fn($carry, $item) => $item['status'] === 'FAIL' ? 'FAIL' : ($item['status'] === 'WARN' && $carry !== 'FAIL' ? 'WARN' : $carry), 'PASS');

        return [
            'status' => $overall,
            'items' => $items,
        ];
    }

    /**
     * Audit standard HTTP security headers.
     *
     * @param array<string, string> $headers
     * @return array{status: string, items: array<array{header: string, status: string, details: string}>}
     */
    public function auditHeaders(array $headers = []): array
    {
        $baseline = [
            'Content-Security-Policy' => 'Prevents XSS, data injection, and malicious execution',
            'X-Frame-Options' => 'Mitigates clickjacking attacks (DENY or SAMEORIGIN)',
            'X-Content-Type-Options' => 'Prevents MIME-sniffing vulnerabilities (nosniff)',
            'Referrer-Policy' => 'Controls referrer information sent in HTTP requests',
            'Permissions-Policy' => 'Restricts sensitive browser features (camera, microphone, geolocation)',
            'Strict-Transport-Security' => 'Enforces HTTPS communication (HSTS)',
        ];

        // Normalize header keys
        $normalized = [];
        foreach ($headers as $k => $v) {
            $normalized[strtolower($k)] = $v;
        }

        $items = [];
        foreach ($baseline as $header => $description) {
            $key = strtolower($header);
            if (isset($normalized[$key]) && !empty($normalized[$key])) {
                $items[] = [
                    'header' => $header,
                    'status' => 'PASS',
                    'details' => "Configured: {$normalized[$key]}",
                ];
            } else {
                $items[] = [
                    'header' => $header,
                    'status' => 'WARN',
                    'details' => "Missing. Recommended: $description",
                ];
            }
        }

        $overall = array_reduce($items, fn($carry, $item) => $item['status'] === 'WARN' ? 'WARN' : $carry, 'PASS');

        return [
            'status' => $overall,
            'items' => $items,
        ];
    }

    /**
     * Audit file and directory permissions.
     *
     * @return array{status: string, items: array<array{path: string, status: string, details: string}>}
     */
    public function auditPermissions(): array
    {
        $items = [];

        $envPath = $this->basePath . '/.env';
        if (file_exists($envPath)) {
            $perms = substr(sprintf('%o', fileperms($envPath)), -4);
            $items[] = [
                'path' => '.env',
                'status' => 'PASS',
                'details' => "Permissions: $perms",
            ];
        } else {
            $items[] = [
                'path' => '.env',
                'status' => 'WARN',
                'details' => 'Environment file missing (.env.example should be copied)',
            ];
        }

        $storagePath = $this->basePath . '/storage';
        if (is_dir($storagePath)) {
            $isWritable = is_writable($storagePath);
            $items[] = [
                'path' => 'storage/',
                'status' => $isWritable ? 'PASS' : 'FAIL',
                'details' => $isWritable ? 'Writable directory' : 'Directory is not writable',
            ];
        } else {
            $items[] = [
                'path' => 'storage/',
                'status' => 'WARN',
                'details' => 'Storage directory does not exist yet',
            ];
        }

        $configPath = $this->basePath . '/config';
        if (is_dir($configPath)) {
            $items[] = [
                'path' => 'config/',
                'status' => 'PASS',
                'details' => 'Readable directory',
            ];
        }

        $overall = array_reduce($items, fn($carry, $item) => $item['status'] === 'FAIL' ? 'FAIL' : ($item['status'] === 'WARN' && $carry !== 'FAIL' ? 'WARN' : $carry), 'PASS');

        return [
            'status' => $overall,
            'items' => $items,
        ];
    }

    /**
     * Run full security audit.
     *
     * @return array<string, mixed>
     */
    public function runFullAudit(): array
    {
        $deps = $this->auditDependencies();
        $perms = $this->auditPermissions();
        $headers = $this->auditHeaders([
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ]);

        $statuses = [$deps['status'], $perms['status'], $headers['status']];
        $overall = in_array('FAIL', $statuses, true) ? 'FAIL' : (in_array('WARN', $statuses, true) ? 'WARN' : 'PASS');

        return [
            'status' => $overall,
            'timestamp' => date('c'),
            'summary' => [
                'dependencies' => $deps['status'],
                'permissions' => $perms['status'],
                'headers' => $headers['status'],
            ],
            'dependencies' => $deps,
            'permissions' => $perms,
            'headers' => $headers,
        ];
    }

    public function toJson(): string
    {
        return (string) json_encode($this->runFullAudit(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

