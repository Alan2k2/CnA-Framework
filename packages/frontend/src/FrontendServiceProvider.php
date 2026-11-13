<?php

declare(strict_types=1);

namespace CnA\Frontend;

use CnA\Contracts\Frontend\AssetManagerInterface;
use CnA\Contracts\Frontend\AssetResolverInterface;
use CnA\Contracts\Frontend\DevServerResolverInterface;
use CnA\Contracts\Frontend\ManifestReaderInterface;
use CnA\Contracts\Frontend\PreloadManagerInterface;
use CnA\Core\ServiceProvider;

class FrontendServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DevServerResolverInterface::class, function () {
            $config = (array) $this->app->make('config')->get('frontend.dev_server', []);
            $url = $config['url'] ?? 'http://127.0.0.1:5173';
            $origins = (array) ($config['allowed_origins'] ?? ['http://127.0.0.1:5173', 'http://localhost:5173', 'http://localhost:8000', 'http://127.0.0.1:8000']);
            $timeout = (float) ($config['timeout'] ?? 0.05);

            return new DevServer($url, $origins, $timeout);
        });
        $this->app->alias(DevServerResolverInterface::class, DevServer::class);

        $this->app->singleton(ManifestReaderInterface::class, function () {
            $config = (array) $this->app->make('config')->get('frontend.build', []);
            $manifest = $config['manifest'] ?? $this->app->publicPath('build/.vite/manifest.json');
            $fallback = $this->app->publicPath('build/manifest.json');

            return new ManifestReader($manifest, $fallback);
        });
        $this->app->alias(ManifestReaderInterface::class, ManifestReader::class);

        $this->app->singleton(PreloadManagerInterface::class, function () {
            return new PreloadManager();
        });
        $this->app->alias(PreloadManagerInterface::class, PreloadManager::class);

        $this->app->singleton(AssetTagRenderer::class, function () {
            return new AssetTagRenderer();
        });

        $this->app->singleton(AssetResolverInterface::class, function () {
            /** @var ManifestReaderInterface $manifest */
            $manifest = $this->app->make(ManifestReaderInterface::class);
            $config = (array) $this->app->make('config')->get('frontend.build', []);
            $buildDir = $config['directory'] ?? $this->app->publicPath('build');
            $publicPath = $config['public_path'] ?? '/build/';
            $entries = (array) $this->app->make('config')->get('frontend.entry_points', []);

            return new AssetResolver($manifest, $buildDir, $publicPath, $entries);
        });
        $this->app->alias(AssetResolverInterface::class, AssetResolver::class);

        $this->app->singleton(AssetManagerInterface::class, function () {
            /** @var DevServerResolverInterface $devServer */
            $devServer = $this->app->make(DevServerResolverInterface::class);
            /** @var AssetResolverInterface $resolver */
            $resolver = $this->app->make(AssetResolverInterface::class);
            /** @var PreloadManagerInterface $preloads */
            $preloads = $this->app->make(PreloadManagerInterface::class);
            /** @var AssetTagRenderer $renderer */
            $renderer = $this->app->make(AssetTagRenderer::class);

            $entryPoints = (array) $this->app->make('config')->get('frontend.entry_points', []);
            $forceDev = $this->app->make('config')->get('frontend.mode') === 'development';

            return new AssetManager($devServer, $resolver, $preloads, $renderer, $entryPoints, $forceDev);
        });
        $this->app->alias(AssetManagerInterface::class, AssetManager::class);
        $this->app->alias(AssetManagerInterface::class, 'cna.frontend');
        $this->app->alias(AssetManagerInterface::class, 'vite');
    }

    public function boot(): void
    {
    }
}

