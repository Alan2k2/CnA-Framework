<?php

declare(strict_types=1);

namespace CnA\Frontend;

use CnA\Contracts\Frontend\AssetManagerInterface;
use CnA\Contracts\Frontend\AssetResolverInterface;
use CnA\Contracts\Frontend\DevServerResolverInterface;
use CnA\Contracts\Frontend\PreloadManagerInterface;

class AssetManager implements AssetManagerInterface
{
    /** @var array<string, string> */
    protected array $entryPoints = [];

    public function __construct(
        protected DevServerResolverInterface $devServer,
        protected AssetResolverInterface $resolver,
        protected PreloadManagerInterface $preloadManager,
        protected AssetTagRenderer $tagRenderer,
        array $entryPoints = [],
        protected bool $forceDev = false
    ) {
        $this->entryPoints = $entryPoints;
    }

    public function isDev(): bool
    {
        return $this->forceDev || $this->devServer->isRunning();
    }

    public function devServerUrl(?string $path = null): string
    {
        return $this->devServer->getUrl($path);
    }

    public function resolve(string $entry): array
    {
        return $this->resolver->resolveEntry($entry);
    }

    public function asset(string $path): string
    {
        if ($this->isDev()) {
            return $this->devServer->getUrl($path);
        }
        return $this->resolver->asset($path);
    }

    public function assets(string|array $entries, ?string $nonce = null): string
    {
        $entryList = is_array($entries) ? $entries : [$entries];
        $html = [];

        // In Development Mode: Vite client + source modules
        if ($this->isDev()) {
            // Include @vite/client once
            $html[] = $this->tagRenderer->renderModuleScriptTag($this->devServer->getUrl('@vite/client'), $nonce);

            foreach ($entryList as $entry) {
                $sourcePath = $this->entryPoints[$entry] ?? $entry;
                $url = $this->devServer->getUrl($sourcePath);

                if (str_ends_with($sourcePath, '.css')) {
                    $html[] = $this->tagRenderer->renderStylesheetTag($url);
                } else {
                    $html[] = $this->tagRenderer->renderModuleScriptTag($url, $nonce);
                }
            }

            return implode("\n", $html);
        }

        // In Production Mode: Resolved manifest assets with preloads & CSS
        foreach ($entryList as $entry) {
            $resolved = $this->resolver->resolveEntry($entry);

            // Collect stylesheets
            foreach ($resolved['css'] as $cssUrl) {
                $this->preloadManager->addStylesheet($cssUrl);
            }

            // Collect modulepreloads
            foreach ($resolved['preloads'] as $preloadUrl) {
                $this->preloadManager->addPreload($preloadUrl);
            }

            // Stylesheets first
            foreach ($this->preloadManager->getStylesheets() as $sheet) {
                $html[] = $this->tagRenderer->renderStylesheetTag($sheet);
            }

            // Preloads next
            foreach ($this->preloadManager->getPreloads() as $preload) {
                $html[] = $this->tagRenderer->renderPreloadTag($preload);
            }

            // Main entry script
            if (str_ends_with($resolved['file'], '.css')) {
                $html[] = $this->tagRenderer->renderStylesheetTag($resolved['file']);
            } else {
                $html[] = $this->tagRenderer->renderModuleScriptTag($resolved['file'], $nonce);
            }
        }

        return implode("\n", array_unique($html));
    }
}
