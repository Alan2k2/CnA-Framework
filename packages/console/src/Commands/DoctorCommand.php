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

        // Advisory patch check: CVE-2026-109618 / CVE-2026-109619
        $isPhpPatched = version_compare(PHP_VERSION, '8.5.0', '>=')
            ? version_compare(PHP_VERSION, '8.5.12', '>=')
            : version_compare(PHP_VERSION, '8.4.27', '>=');

        $sessionUploadDisabled = ini_get('session.upload_progress.enabled') !== '1';

        $openSslVer = defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'Installed';
        $sqliteVer = class_exists(\SQLite3::class) ? \SQLite3::version()['versionString'] : 'Installed';

        $checks = [
            [
                'PHP Runtime Baseline (>= 8.4)',
                version_compare(PHP_VERSION, '8.4.0', '>='),
                'Installed: ' . PHP_VERSION,
            ],
            [
                'PHP Security Patch Level',
                $isPhpPatched,
                $isPhpPatched ? 'Patched against CVE-2026-109618/19' : 'WARNING: Vulnerable to CVE-2026-109618/19 RCE. Upgrade to >= 8.4.27 or >= 8.5.12',
            ],
            [
                'Session Upload Progress (CVE-2026-115560)',
                $sessionUploadDisabled,
                $sessionUploadDisabled ? 'Disabled (Mitigated)' : 'WARNING: session.upload_progress is enabled. Set to 0 in php.ini',
            ],
            ['PDO Extension', extension_loaded('pdo'), 'Installed'],
            ['PDO SQLite Driver', extension_loaded('pdo_sqlite'), 'Linked SQLite: ' . $sqliteVer],
            ['OpenSSL Extension', extension_loaded('openssl'), $openSslVer],
            ['MBString Extension', extension_loaded('mbstring'), 'Installed'],
            ['JSON Extension', extension_loaded('json'), 'Installed'],
            ['Storage Directory Writable', $storageWritable, $this->app->storagePath()],
            ['Environment (.env) Exists', file_exists($this->app->basePath('.env')), file_exists($this->app->basePath('.env')) ? 'Found' : 'Missing (Copy .env.example)'],
        ];

        $rows = [];
        $hasFailure = false;

        foreach ($checks as [$label, $passed, $details]) {
            if (!$passed && $label !== 'PHP Security Patch Level' && $label !== 'Session Upload Progress (CVE-2026-115560)') {
                $hasFailure = true;
                $status = "\033[31mFAIL\033[0m";
            } elseif (!$passed) {
                // Advisory warnings do not necessarily halt dev CLI, but alert user
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
