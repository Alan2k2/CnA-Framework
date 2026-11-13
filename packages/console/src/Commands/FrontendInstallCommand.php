<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class FrontendInstallCommand extends Command
{
    protected string $name = 'frontend:install';
    protected string $description = 'Scaffold baseline Vite configuration, package.json, and frontend asset directories';

    public function handle(): int
    {
        $this->info("Initializing CnA Frontend Engine & Vite Integration...\n");

        $base = $this->app->basePath();

        // 1. Ensure resources directories exist
        $dirs = [
            $base . '/resources/js',
            $base . '/resources/css',
            $base . '/resources/modules',
            $base . '/resources/views/layouts',
            $base . '/resources/views/components',
            $base . '/public/build',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->line("  <info>Created directory:</info> " . str_replace($base, '', $dir));
            }
        }

        // 2. Scaffold package.json if missing
        $packageJsonPath = $base . '/package.json';
        if (!file_exists($packageJsonPath)) {
            $packageJson = [
                'name' => 'cna-frontend',
                'private' => true,
                'version' => '2.0.0',
                'type' => 'module',
                'scripts' => [
                    'dev' => 'vite',
                    'build' => 'vite build',
                    'preview' => 'vite preview',
                ],
                'devDependencies' => [
                    'vite' => '^5.4.11',
                    'typescript' => '^5.6.3',
                ],
            ];
            file_put_contents($packageJsonPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
            $this->info("  Created: package.json");
        }

        // 3. Scaffold vite.config.js if missing
        $viteConfigPath = $base . '/vite.config.js';
        if (!file_exists($viteConfigPath)) {
            $viteConfig = <<<JS
import { defineConfig } from 'vite';

export default defineConfig({
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
    origin: 'http://127.0.0.1:5173',
  },
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: [
        'resources/js/app.ts',
        'resources/css/app.css',
      ],
    },
  },
});
JS;
            file_put_contents($viteConfigPath, $viteConfig . PHP_EOL);
            $this->info("  Created: vite.config.js");
        }

        // 4. Scaffold resources/js/app.ts
        $appTsPath = $base . '/resources/js/app.ts';
        if (!file_exists($appTsPath)) {
            $appTs = <<<TS
// CnA Framework v2.0 - Frontend Engine Entry
import '../css/app.css';

console.log('⚡ CnA Frontend Engine v2.0 initialized with Vite HMR');

document.addEventListener('DOMContentLoaded', () => {
    // Progressive enhancement hooks
});
TS;
            file_put_contents($appTsPath, $appTs . PHP_EOL);
            $this->info("  Created: resources/js/app.ts");
        }

        // 5. Scaffold resources/css/app.css
        $appCssPath = $base . '/resources/css/app.css';
        if (!file_exists($appCssPath)) {
            $appCss = <<<CSS
/* CnA Framework v2.0 Design Tokens & Theme Variables */
:root {
    --cna-bg: #090d16;
    --cna-surface: #0f172a;
    --cna-border: #1e293b;
    --cna-primary: #06b6d4;
    --cna-primary-hover: #0891b2;
    --cna-text-main: #f8fafc;
    --cna-text-muted: #94a3b8;
}

body {
    background-color: var(--cna-bg);
    color: var(--cna-text-main);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    margin: 0;
}
CSS;
            file_put_contents($appCssPath, $appCss . PHP_EOL);
            $this->info("  Created: resources/css/app.css");
        }

        $this->info("\nFrontend baseline scaffolded successfully!");
        $this->line("Next steps:");
        $this->line("  1. Run <info>npm install</info> to install Vite and TypeScript");
        $this->line("  2. Run <info>php cna frontend:dev</info> or <info>npm run dev</info> to start Vite HMR server");
        $this->line("  3. Run <info>php cna serve</info> to start your CnA PHP application");

        return Command::SUCCESS;
    }
}
