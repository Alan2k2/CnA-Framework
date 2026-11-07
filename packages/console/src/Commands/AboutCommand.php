<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class AboutCommand extends Command
{
    protected string $name = 'about';
    protected string $description = 'Display basic information about the CnA Framework installation';

    public function handle(): int
    {
        $this->info("CnA Framework Environment Information");
        $this->table(['Key', 'Value'], [
            ['Framework Version', $this->app->version()],
            ['PHP Version', PHP_VERSION],
            ['Environment', $this->app->environment()],
            ['Base Path', $this->app->basePath()],
            ['Config Path', $this->app->configPath()],
            ['Storage Path', $this->app->storagePath()],
            ['OPcache Enabled', function_exists('opcache_get_status') && opcache_get_status() !== false ? 'Yes' : 'No'],
        ]);

        return Command::SUCCESS;
    }
}

