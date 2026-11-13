<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class AppInfoCommand extends Command
{
    protected string $name = 'app:info';
    protected string $description = 'Display comprehensive platform, environment, and ecosystem architecture status';

    public function handle(): int
    {
        $version = defined('CNA_VERSION') ? CNA_VERSION : '2.1.0';

        $this->info("CnA Modular Application Platform\n");

        $rows = [
            ['Framework Version', $version],
            ['PHP Version', PHP_VERSION],
            ['PHP Interface (SAPI)', PHP_SAPI],
            ['Base Path', $this->app->basePath()],
            ['Storage Path', $this->app->storagePath()],
            ['Environment Mode', env('APP_ENV', 'production')],
            ['Debug Mode', env('APP_DEBUG', false) ? 'Enabled' : 'Disabled'],
            ['Ecosystem: Workflow Engine', $this->app->has('workflow') ? "\033[32mActive\033[0m" : 'Available'],
            ['Ecosystem: Multi-Tenancy', $this->app->has('tenancy') ? "\033[32mActive\033[0m" : 'Available'],
            ['Ecosystem: Feature Flags', $this->app->has('features') ? "\033[32mActive\033[0m" : 'Available'],
            ['Ecosystem: Plugin Manager', $this->app->has('plugins') ? "\033[32mActive\033[0m" : 'Available'],
            ['Ecosystem: DevTools & Tracing', $this->app->has('tracer') ? "\033[32mActive\033[0m" : 'Available'],
            ['Ecosystem: Frontend Engine', class_exists(\CnA\Frontend\FrontendManager::class) ? "\033[32mActive\033[0m" : 'Available'],
        ];

        $this->table(['Architecture Component', 'Status / Value'], $rows);
        return Command::SUCCESS;
    }
}
