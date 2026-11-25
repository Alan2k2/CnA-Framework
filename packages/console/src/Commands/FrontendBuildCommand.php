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

            $buildDir = dirname($targetManifest);
            if (basename($buildDir) === '.vite') {
                $buildDir = dirname($buildDir);
            }

            $missingFiles = [];
            foreach ($manifest as $key => $entry) {
                $isEntry = !empty($entry['isEntry']) ? ' (Entry)' : '';
                $this->line("  - \033[32m{$key}\033[0m -> {$entry['file']}{$isEntry}");

                $file = $entry['file'] ?? null;
                if ($file) {
                    $diskPath = $buildDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
                    if (!file_exists($diskPath)) {
                        $missingFiles[] = $file;
                    }
                }

                if (!empty($entry['css'])) {
                    foreach ($entry['css'] as $cssFile) {
                        $diskPath = $buildDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cssFile);
                        if (!file_exists($diskPath)) {
                            $missingFiles[] = $cssFile;
                        }
                    }
                }
            }

            if (!empty($missingFiles)) {
                $this->error("\nManifest validation failed: " . count($missingFiles) . " referenced output file(s) are missing from disk:");
                foreach (array_slice($missingFiles, 0, 10) as $missing) {
                    $this->line("  - \033[31m{$missing}\033[0m");
                }
                return Command::FAILURE;
            }

            $this->info("\nAll manifest output artifacts verified on disk.");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Manifest validation failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

