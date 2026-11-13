<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Contracts\Frontend\ManifestReaderInterface;

class FrontendBuildCommand extends Command
{
    protected string $name = 'frontend:build';
    protected string $description = 'Verify production Vite build artifacts and manifest';

    public function handle(): int
    {
        $this->info("Verifying Production Frontend Build Artifacts...\n");

        $manifestConfig = (string) $this->app->make('config')->get('frontend.build.manifest', $this->app->publicPath('build/.vite/manifest.json'));
        $fallback = $this->app->publicPath('build/manifest.json');

        $targetManifest = file_exists($manifestConfig) ? $manifestConfig : (file_exists($fallback) ? $fallback : null);

        if ($targetManifest === null) {
            $this->warn("Vite production manifest not found in build directory.");
            $this->line("To compile assets for production, run:");
            $this->line("  \033[36mnpm run build\033[0m\n");
            return Command::FAILURE;
        }

        try {
            /** @var ManifestReaderInterface $reader */
            $reader = $this->app->make(ManifestReaderInterface::class);
            $reader->clear();
            $manifest = $reader->load();

            $this->info("Manifest verified successfully at [{$targetManifest}].");
            $this->line("Indexed entries: " . count($manifest));

            foreach ($manifest as $key => $entry) {
                $isEntry = !empty($entry['isEntry']) ? ' (Entry)' : '';
                $this->line("  - \033[32m{$key}\033[0m -> {$entry['file']}{$isEntry}");
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Manifest validation failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

