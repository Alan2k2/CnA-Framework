<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class EnvCheckCommand extends Command
{
    protected string $name = 'env:check';
    protected string $description = 'Verify environment variables against .env.example definitions';

    public function handle(): int
    {
        $envExamplePath = $this->app->basePath('.env.example');
        $envPath = $this->app->basePath('.env');

        if (!file_exists($envExamplePath)) {
            $this->warn('.env.example file not found.');
            return Command::SUCCESS;
        }

        $exampleLines = file($envExamplePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $exampleKeys = [];
        foreach ($exampleLines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key] = explode('=', $line, 2);
                $exampleKeys[] = trim($key);
            }
        }

        $activeKeys = [];
        if (file_exists($envPath)) {
            $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($envLines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$key] = explode('=', $line, 2);
                    $activeKeys[] = trim($key);
                }
            }
        }

        $missing = array_diff($exampleKeys, $activeKeys);
        $rows = [];

        foreach ($exampleKeys as $key) {
            $present = in_array($key, $activeKeys, true);
            $rows[] = [
                $key,
                $present ? "\033[32mDEFINED\033[0m" : "\033[33mMISSING\033[0m",
            ];
        }

        $this->table(['Environment Variable', 'Status in .env'], $rows);

        if (!empty($missing)) {
            $this->warn(sprintf("\nFound %d missing environment variable(s) compared to .env.example.", count($missing)));
            return Command::SUCCESS;
        }

        $this->info("\nAll environment variables are synchronized with .env.example.");
        return Command::SUCCESS;
    }
}
