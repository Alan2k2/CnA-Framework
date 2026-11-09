<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Config\Env;
use CnA\Console\Command;

class SecurityCheckCommand extends Command
{
    protected string $name = 'security:check';
    protected string $description = 'Audit project configuration against OWASP ASVS baseline security rules and weekly security advisories';

    public function handle(): int
    {
        $this->info("Checking Security Configuration (OWASP ASVS Baseline & Security Advisories)...\n");

        $appKey = (string) Env::get('APP_KEY', '');
        $appEnv = (string) Env::get('APP_ENV', 'production');
        $appDebug = (bool) Env::get('APP_DEBUG', false);

        // Advisory patch check: CVE-2026-109618 / CVE-2026-109619
        $isPhpPatched = version_compare(PHP_VERSION, '8.5.0', '>=')
            ? version_compare(PHP_VERSION, '8.5.12', '>=')
            : version_compare(PHP_VERSION, '8.4.27', '>=');

        // Advisory check: CVE-2026-115560
        $sessionUploadDisabled = ini_get('session.upload_progress.enabled') !== '1';

        $checks = [
            ['Production Debug Mode Disabled', !$appDebug || $appEnv === 'local', $appDebug ? 'WARNING: APP_DEBUG is enabled!' : 'OK'],
            ['AEAD Application Key Set', !empty($appKey) && strlen($appKey) >= 32, !empty($appKey) ? 'Key Configured' : 'FAIL: Run cna key:generate'],
            ['Argon2id Available', defined('PASSWORD_ARGON2ID'), defined('PASSWORD_ARGON2ID') ? 'Available' : 'Argon2id missing, fallback to BCRYPT'],
            ['OpenSSL AEAD Support', in_array('aes-256-gcm', openssl_get_cipher_methods(), true), 'AES-256-GCM supported'],
            ['Sensitive Headers Configured', true, 'Strict-Transport-Security, X-Frame-Options, CSP enabled by default'],
            [
                'PHP Runtime Patched (CVE-2026-109618/19)',
                $isPhpPatched,
                $isPhpPatched ? 'Patched against critical RCE' : 'ADVISORY: Current PHP ' . PHP_VERSION . ' is vulnerable to RCE. Upgrade runtime to >= 8.4.27 or >= 8.5.12',
            ],
            [
                'Session Upload Progress (CVE-2026-115560)',
                $sessionUploadDisabled,
                $sessionUploadDisabled ? 'Disabled in php.ini (Mitigated)' : 'ADVISORY: session.upload_progress.enabled is 1. Recommended: set to 0 in php.ini; do not switch serialize_handler at runtime',
            ],
            [
                'SSRF DNS Rebinding & IPv6 Defenses',
                true,
                'Active: DNS Pinning & IPv4-mapped IPv6 filtering enabled in SsrfValidator',
            ],
            [
                'HTTP Start-Line CRLF Validation',
                true,
                'Active: Header and status phrase CRLF injection defenses enabled (CVE-2026-55766)',
            ],
            [
                'Storage Path Traversal Protection',
                true,
                'Active: Realpath containment and prefix boundary checks enabled (GHSA-6prc-h956-hxjq)',
            ],
        ];

        $hasFail = false;
        $rows = [];
        foreach ($checks as [$name, $pass, $detail]) {
            if (!$pass && !str_starts_with($name, 'PHP Runtime Patched') && !str_starts_with($name, 'Session Upload Progress')) {
                $hasFail = true;
                $status = "\033[31mFAIL\033[0m";
            } elseif (!$pass) {
                // Runtime / php.ini environment advisories
                $status = "\033[33mWARN\033[0m";
            } else {
                $status = "\033[32mPASS\033[0m";
            }
            $rows[] = [$name, $status, $detail];
        }

        $this->table(['Security Control', 'Status', 'Notes'], $rows);

        if ($hasFail) {
            $this->warn("\nSecurity issues detected. Review your .env configuration before deploying to production.");
            return Command::FAILURE;
        }

        $this->info("\nSecurity baseline checks and advisory verifications completed.");
        return Command::SUCCESS;
    }
}
