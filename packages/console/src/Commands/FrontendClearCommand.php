<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Contracts\Frontend\ManifestReaderInterface;
use CnA\Contracts\Frontend\PreloadManagerInterface;

class FrontendClearCommand extends Command
{
    protected string $name = 'frontend:clear';
    protected string $description = 'Clear cached frontend manifest and preloaded asset metadata';

    public function handle(): int
    {
        $this->info("Clearing Frontend Engine Manifest & Preload Caches...\n");

        if ($this->app->has(ManifestReaderInterface::class)) {
            /** @var ManifestReaderInterface $reader */
            $reader = $this->app->make(ManifestReaderInterface::class);
            $reader->clear();
        }

        if ($this->app->has(PreloadManagerInterface::class)) {
            /** @var PreloadManagerInterface $preloads */
            $preloads = $this->app->make(PreloadManagerInterface::class);
            $preloads->clear();
        }

        $this->info("Frontend asset cache cleared successfully.");
        return Command::SUCCESS;
    }
}
