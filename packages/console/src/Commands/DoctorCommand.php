<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class DoctorCommand extends Command
{
    protected string $name = 'doctor';
    protected string $description = 'Perform diagnostic health checks on runtime, extensions, and security advisories';

    public function handle(): int
    {
        $this->info("Running CnA Framework Diagnostics...\n");

        $storageWritable = $this->isDirWritable($this->app->storagePath());

        // Advisory patch check: 19 Nov 2026 Security Release (GHSA-qc49-hpvq-g2w6, GHSA-658j-r8rw-q6mx)
        $isPhpPatched = version_compare(PHP_VERSION, '8.5.0', '>=')
            ? version_compare(PHP_VERSION, '8.5.13', '>=')
            : version_compare(PHP_VERSION, '8.4.28', '>=');

        $sessionUploadDisabled = ini_get('session.upload_progress.enabled') !== '1';

        $openSslVer = defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'Installed';
        
        $sqliteVer = class_exists(\SQLite3::class) ? \SQLite3::version()['versionString'] : 'Installed';
        $isSqlitePatched = class_exists(\SQLite3::class) ? version_compare($sqliteVer, '3.54.1', '>=') : true;

        $curlVer = 'N/A';
        $isCurlPatched = true;
        if (extension_loaded('curl')) {
            $curlInfo = curl_version();
            $curlVer = $curlInfo['version'] ?? 'Unknown';
            $isCurlPatched = version_compare($curlVer, '8.23.0', '>=');
        }

        $envExists = file_exists($this->app->basePath('.env'));
        $envExampleExists = file_exists($this->app->basePath('.env.example'));

        $checks = [
            [
                'PHP Runtime Baseline (>= 8.4)',
                version_compare(PHP_VERSION, '8.4.0', '>='),
                'Installed: ' . PHP_VERSION,
            ],
            [
                'PHP Security Patch Level (19 Nov 2026)',
                $isPhpPatched,
                $isPhpPatched ? 'Patched (>= 8.4.28 / 8.5.13 - GHSA-qc49-hpvq-g2w6 & session RCEs)' : 'WARNING: Vulnerable to PHP-FPM smuggling & session RCE (GHSA-qc49-hpvq-g2w6). Upgrade to >= 8.4.28 or >= 8.5.13',
            ],
            [
                'Session Upload Progress (CVE-2026-115560 / GHSA-658j-r8rw-q6mx)',
                $sessionUploadDisabled,
                $sessionUploadDisabled ? 'Disabled (Mitigated)' : 'WARNING: session.upload_progress is enabled. Set to 0 in php.ini',
            ],
            ['PDO Extension', extension_loaded('pdo'), 'Installed'],
            ['PDO SQLite Driver', extension_loaded('pdo_sqlite'), 'Linked SQLite: ' . $sqliteVer],
            [
                'SQLite Version Baseline',
                $isSqlitePatched,
                $isSqlitePatched ? 'SQLite ' . $sqliteVer . ' (>= 3.54.1)' : 'WARNING: SQLite ' . $sqliteVer . ' < 3.54.1 (CVE-2026-119371..591)',
            ],
            ['OpenSSL Extension', extension_loaded('openssl'), $openSslVer],
            [
                'libcurl Transport Security',
                $isCurlPatched,
                $isCurlPatched ? 'libcurl ' . $curlVer . ' (>= 8.23.0)' : 'WARNING: libcurl ' . $curlVer . ' < 8.23.0 (CVE-2026-92392)',
            ],
            ['MBString Extension', extension_loaded('mbstring'), 'Installed'],
            ['JSON Extension', extension_loaded('json'), 'Installed'],
            ['Storage Directory Writable', $storageWritable, $this->app->storagePath()],
            ['Environment (.env) Exists', $envExists, $envExists ? 'Found' : ($envExampleExists ? 'Missing (.env.example available)' : 'Missing')],
        ];

        $rows = [];
        $hasFailure = false;
        $advisoryChecks = [
            'PHP Security Patch Level (19 Nov 2026)',
            'Session Upload Progress (CVE-2026-115560 / GHSA-658j-r8rw-q6mx)',
            'SQLite Version Baseline',
            'libcurl Transport Security',
            'Environment (.env) Exists',
        ];

        foreach ($checks as [$label, $passed, $details]) {
            if (!$passed && !in_array($label, $advisoryChecks, true)) {
                $hasFailure = true;
                $status = "\033[31mFAIL\033[0m";
            } elseif (!$passed) {
                // Advisory warnings alert developer without halting dev CLI
                $status = "\033[33mWARN\033[0m";
            } else {
                $status = "\033[32mPASS\033[0m";
            }
            $rows[] = [$label, $status, $details];
        }

        $this->table(['Diagnostic Check', 'Status', 'Details'], $rows);

        if ($hasFailure) {
            $this->warn("\nSome diagnostic checks failed. Please address them before running in production.");
            return Command::FAILURE;
        }

        $this->info("\nAll environment checks passed successfully! Your CnA Framework setup is healthy.");
        return Command::SUCCESS;
    }

    protected function isDirWritable(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }
        $testFile = rtrim($path, '/\\') . '/.cna_write_test_' . uniqid();
        $written = @file_put_contents($testFile, 'test');
        if ($written !== false) {
            @unlink($testFile);
            return true;
        }
        return false;
    }
}
