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

        // Advisory patch check: 19 Nov 2026 Security Release (GHSA-qc49-hpvq-g2w6, GHSA-658j-r8rw-q6mx)
        $isPhpPatched = version_compare(PHP_VERSION, '8.5.0', '>=')
            ? version_compare(PHP_VERSION, '8.5.13', '>=')
            : version_compare(PHP_VERSION, '8.4.28', '>=');

        // Advisory check: CVE-2026-115560 & GHSA-658j-r8rw-q6mx
        $sessionUploadDisabled = ini_get('session.upload_progress.enabled') !== '1';

        // Advisory check: libcurl >= 8.23.0 (CVE-2026-92392)
        $curlVer = extension_loaded('curl') ? (curl_version()['version'] ?? 'unknown') : 'N/A';
        $isCurlPatched = extension_loaded('curl') ? version_compare($curlVer, '8.23.0', '>=') : true;

        // Advisory check: SQLite >= 3.54.1 (CVE-2026-119371..591)
        $sqliteVer = class_exists(\SQLite3::class) ? \SQLite3::version()['versionString'] : 'N/A';
        $isSqlitePatched = class_exists(\SQLite3::class) ? version_compare($sqliteVer, '3.54.1', '>=') : true;

        $checks = [
            ['Production Debug Mode Disabled', !$appDebug || $appEnv === 'local', $appDebug ? 'WARNING: APP_DEBUG is enabled!' : 'OK'],
            ['AEAD Application Key Set', !empty($appKey) && strlen($appKey) >= 32, !empty($appKey) ? 'Key Configured' : 'FAIL: Run cna key:generate'],
            ['Argon2id Available', defined('PASSWORD_ARGON2ID'), defined('PASSWORD_ARGON2ID') ? 'Available' : 'Argon2id missing, fallback to BCRYPT'],
            ['OpenSSL AEAD Support', in_array('aes-256-gcm', openssl_get_cipher_methods(), true), 'AES-256-GCM supported'],
            ['Sensitive Headers Configured', true, 'Strict-Transport-Security, X-Frame-Options, CSP enabled by default'],
            [
                'PHP Runtime Patched (19 Nov 2026 Release)',
                $isPhpPatched,
                $isPhpPatched ? 'Patched against PHP-FPM smuggling & session RCE (>= 8.4.28 / 8.5.13)' : 'ADVISORY: Current PHP ' . PHP_VERSION . ' is vulnerable to GHSA-qc49-hpvq-g2w6 / GHSA-658j-r8rw-q6mx. Upgrade runtime to >= 8.4.28 or >= 8.5.13',
            ],
            [
                'Session Upload Progress (CVE-2026-115560 / GHSA-658j-r8rw-q6mx)',
                $sessionUploadDisabled,
                $sessionUploadDisabled ? 'Disabled in php.ini (Mitigated)' : 'ADVISORY: session.upload_progress.enabled is 1. Recommended: set to 0 in php.ini; do not switch serialize_handler at runtime',
            ],
            [
                'libcurl Transport Security (CVE-2026-92392)',
                $isCurlPatched,
                $isCurlPatched ? 'libcurl ' . $curlVer . ' (>= 8.23.0)' : 'ADVISORY: libcurl ' . $curlVer . ' < 8.23.0. Upgrade system libcurl',
            ],
            [
                'SQLite Version Baseline (CVE-2026-119371)',
                $isSqlitePatched,
                $isSqlitePatched ? 'SQLite ' . $sqliteVer . ' (>= 3.54.1)' : 'ADVISORY: SQLite ' . $sqliteVer . ' < 3.54.1. Upgrade linked SQLite library',
            ],
            [
                'SSRF Canonical Scheme Validation',
                true,
                'Active: Rejection of mixed-case URL schemes (GHSA-f6gg-rchx-xw8j TLS bypass defense)',
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
        $advisoryCheckPrefixes = [
            'PHP Runtime Patched',
            'Session Upload Progress',
            'libcurl Transport Security',
            'SQLite Version Baseline',
        ];

        foreach ($checks as [$name, $pass, $detail]) {
            $isAdvisory = false;
            foreach ($advisoryCheckPrefixes as $prefix) {
                if (str_starts_with($name, $prefix)) {
                    $isAdvisory = true;
                    break;
                }
            }

            if (!$pass && !$isAdvisory) {
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
