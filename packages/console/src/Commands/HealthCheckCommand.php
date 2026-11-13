<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class HealthCheckCommand extends Command
{
    protected string $name = 'health:check';
    protected string $description = 'Perform operational health check on runtime, memory, and database';

    public function handle(): int
    {
        $this->info("Checking system health...\n");

        $checks = [];

        // 1. PHP Version
        $checks[] = [
            'PHP Runtime',
            version_compare(PHP_VERSION, '8.4.0', '>=') ? "\033[32mHEALTHY\033[0m" : "\033[33mDEGRADED\033[0m",
            'Version ' . PHP_VERSION,
        ];

        // 2. Memory Usage
        $memUsage = memory_get_usage(true) / 1024 / 1024;
        $memLimit = ini_get('memory_limit');
        $checks[] = [
            'Memory Allocation',
            "\033[32mHEALTHY\033[0m",
            sprintf('%.2f MB used (Limit: %s)', $memUsage, $memLimit),
        ];

        // 3. Storage Directory
        $storage = $this->app->storagePath();
        $storageHealthy = is_dir($storage) && is_writable($storage);
        $checks[] = [
            'Storage Subsystem',
            $storageHealthy ? "\033[32mHEALTHY\033[0m" : "\033[31mUNHEALTHY\033[0m",
            $storageHealthy ? 'Storage path is writable' : 'Storage path is not writable or missing',
        ];

        // 4. Database Connection (if configured)
        if ($this->app->has('db')) {
            try {
                $db = $this->app->make('db');
                if (method_exists($db, 'connection')) {
                    $db->connection()->select('SELECT 1');
                }
                $checks[] = ['Database Connection', "\033[32mHEALTHY\033[0m", 'Connected'];
            } catch (\Throwable $e) {
                $checks[] = ['Database Connection', "\033[33mDEGRADED\033[0m", 'Error: ' . $e->getMessage()];
            }
        }

        // 5. Cache subsystem (if configured)
        if ($this->app->has('cache')) {
            try {
                $cache = $this->app->make('cache');
                $testKey = 'health_check_' . uniqid();
                $cache->set($testKey, 'ok', 5);
                $cached = $cache->get($testKey);
                $cache->delete($testKey);
                $checks[] = ['Cache Subsystem', $cached === 'ok' ? "\033[32mHEALTHY\033[0m" : "\033[33mDEGRADED\033[0m", 'Operational'];
            } catch (\Throwable $e) {
                $checks[] = ['Cache Subsystem', "\033[33mDEGRADED\033[0m", 'Error: ' . $e->getMessage()];
            }
        }

        $this->table(['Component', 'Status', 'Details'], $checks);
        $this->info("\nSystem health checks completed.");
        return Command::SUCCESS;
    }
}
