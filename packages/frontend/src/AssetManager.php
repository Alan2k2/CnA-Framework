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

    protected bool $clientScriptEmitted = false;

    /** @var array<string, bool> */
    protected array $renderedTags = [];

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

    public function reset(): void
    {
        $this->clientScriptEmitted = false;
        $this->renderedTags = [];
        $this->preloadManager->clear();
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
            // Include @vite/client once per request/lifecycle
            if (!$this->clientScriptEmitted) {
                $clientTag = $this->tagRenderer->renderModuleScriptTag($this->devServer->getUrl('@vite/client'), $nonce);
                if (!isset($this->renderedTags[$clientTag])) {
                    $html[] = $clientTag;
                    $this->renderedTags[$clientTag] = true;
                }
                $this->clientScriptEmitted = true;
            }

            foreach ($entryList as $entry) {
                $sourcePath = $this->entryPoints[$entry] ?? $entry;
                $url = $this->devServer->getUrl($sourcePath);

                $tag = str_ends_with($sourcePath, '.css')
                    ? $this->tagRenderer->renderStylesheetTag($url)
                    : $this->tagRenderer->renderModuleScriptTag($url, $nonce);

                if (!isset($this->renderedTags[$tag])) {
                    $html[] = $tag;
                    $this->renderedTags[$tag] = true;
                }
            }

            return implode("\n", $html);
        }

        // In Production Mode: Resolved manifest assets with preloads & CSS
        // Vite backend integration order: 1. Entry CSS, 2. Entry script, 3. Modulepreloads
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

            // 1. Stylesheets first
            foreach ($this->preloadManager->getStylesheets() as $sheet) {
                $tag = $this->tagRenderer->renderStylesheetTag($sheet);
                if (!isset($this->renderedTags[$tag])) {
                    $html[] = $tag;
                    $this->renderedTags[$tag] = true;
                }
            }

            // 2. Main entry script next (Vite backend integration guide order)
            $mainTag = str_ends_with($resolved['file'], '.css')
                ? $this->tagRenderer->renderStylesheetTag($resolved['file'])
                : $this->tagRenderer->renderModuleScriptTag($resolved['file'], $nonce);

            if (!isset($this->renderedTags[$mainTag])) {
                $html[] = $mainTag;
                $this->renderedTags[$mainTag] = true;
            }

            // 3. Modulepreloads
            foreach ($this->preloadManager->getPreloads() as $preload) {
                $tag = $this->tagRenderer->renderPreloadTag($preload);
                if (!isset($this->renderedTags[$tag])) {
                    $html[] = $tag;
                    $this->renderedTags[$tag] = true;
                }
            }
        }

        return implode("\n", $html);
    }
}

