<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class DoctorCommand extends Command
{
    protected string $name = 'doctor';
    protected string $description = 'Perform diagnostic health checks on runtime and environment';

    public function handle(): int
    {
        $this->info("Running CnA Framework Diagnostics...\n");

        $storageWritable = $this->isDirWritable($this->app->storagePath());

        $checks = [
            ['PHP Version (>= 8.4)', version_compare(PHP_VERSION, '8.4.0', '>='), 'Installed: ' . PHP_VERSION],
            ['PDO Extension', extension_loaded('pdo'), 'Installed'],
            ['PDO SQLite Driver', extension_loaded('pdo_sqlite'), extension_loaded('pdo_sqlite') ? 'Installed' : 'Missing'],
            ['OpenSSL Extension', extension_loaded('openssl'), 'Installed'],
            ['MBString Extension', extension_loaded('mbstring'), 'Installed'],
            ['JSON Extension', extension_loaded('json'), 'Installed'],
            ['Storage Directory Writable', $storageWritable, $this->app->storagePath()],
            ['Environment (.env) Exists', file_exists($this->app->basePath('.env')), file_exists($this->app->basePath('.env')) ? 'Found' : 'Missing (Copy .env.example)'],
        ];

        $rows = [];
        $hasFailure = false;

        foreach ($checks as [$label, $passed, $details]) {
            if (!$passed) {
                $hasFailure = true;
            }
            $status = $passed ? "\033[32mPASS\033[0m" : "\033[31mFAIL\033[0m";
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
