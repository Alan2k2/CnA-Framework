<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Contracts\Frontend\AssetManagerInterface;
use CnA\Contracts\Frontend\DevServerResolverInterface;
use CnA\Contracts\Frontend\ManifestReaderInterface;

class FrontendStatusCommand extends Command
{
    protected string $name = 'frontend:status';
    protected string $description = 'Report frontend engine status, Vite dev-server reachability, manifest, and build outputs';

    public function handle(): int
    {
        $this->info("CnA Frontend Engine Status Report (Version 2.0.0)\n");

        $config = (array) $this->app->make('config')->get('frontend', []);
        $mode = $config['mode'] ?? 'development';
        $devUrl = $config['dev_server']['url'] ?? 'http://127.0.0.1:5173';
        $manifestPath = $config['build']['manifest'] ?? $this->app->publicPath('build/.vite/manifest.json');
        $fallbackManifest = $this->app->publicPath('build/manifest.json');
        $buildDir = $config['build']['directory'] ?? $this->app->publicPath('build');
        $entryPoints = $config['entry_points'] ?? [];

        /** @var DevServerResolverInterface $devServer */
        $devServer = $this->app->make(DevServerResolverInterface::class);
        $isViteRunning = $devServer->isRunning();

        $manifestFound = file_exists($manifestPath) ? $manifestPath : (file_exists($fallbackManifest) ? $fallbackManifest : null);

        $rows = [
            ['Frontend Subsystem', 'ENABLED', 'CnA Frontend Engine v2.0 active'],
            ['Operating Mode', strtoupper($mode), $mode === 'development' ? 'Serving from Vite HMR / source' : 'Serving hashed assets from manifest'],
            [
                'Vite Dev Server',
                $isViteRunning ? "\033[32mRUNNING\033[0m" : "\033[33mSTOPPED\033[0m",
                $devUrl . ($isViteRunning ? ' (Connected)' : ' (Not detected - run `npm run dev`)'),
            ],
            [
                'Vite Manifest',
                $manifestFound ? "\033[32mFOUND\033[0m" : "\033[31mMISSING\033[0m",
                $manifestFound ? $manifestFound : "Run `php cna frontend:build` or `npm run build`",
            ],
            [
                'Public Build Dir',
                is_dir($buildDir) ? "\033[32mREADY\033[0m" : "\033[33mNOT CREATED\033[0m",
                $buildDir,
            ],
        ];

        $this->table(['Frontend Component', 'Status', 'Details'], $rows);

        $this->line("\n\033[33mConfigured Entry Points:\033[0m");
        foreach ($entryPoints as $name => $src) {
            $this->line("  - \033[36m" . str_pad($name, 16) . "\033[0m -> " . $src);
        }

        return Command::SUCCESS;
    }
}

