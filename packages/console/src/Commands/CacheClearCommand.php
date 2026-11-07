<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Contracts\Cache\CacheInterface;

class CacheClearCommand extends Command
{
    protected string $name = 'cache:clear';
    protected string $description = 'Flush the application cache';

    public function handle(): int
    {
        /** @var CacheInterface $cache */
        $cache = $this->app->get(CacheInterface::class);
        $cache->clear();

        $this->info("Application cache cleared successfully.");
        return Command::SUCCESS;
    }
}

