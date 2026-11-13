<?php

declare(strict_types=1);

namespace CnA\Frontend\Facades;

use CnA\Contracts\Frontend\AssetManagerInterface;
use CnA\Frontend\AssetTagRenderer;

class Vite
{
    /**
     * Get the resolved AssetManagerInterface instance from the container.
     */
    protected static function getManager(): AssetManagerInterface
    {
        if (function_exists('app') && app()->has(AssetManagerInterface::class)) {
            return app(AssetManagerInterface::class);
        }

        // Fallback standalone instance
        $devServer = new \CnA\Frontend\DevServer();
        $reader = new \CnA\Frontend\ManifestReader('public/build/.vite/manifest.json', 'public/build/manifest.json');
        $resolver = new \CnA\Frontend\AssetResolver($reader, 'public/build');
        $preloads = new \CnA\Frontend\PreloadManager();
        $renderer = new AssetTagRenderer();

        return new \CnA\Frontend\AssetManager($devServer, $resolver, $preloads, $renderer);
    }

    /**
     * Render Vite asset tags (scripts, CSS, modulepreloads).
     */
    public static function assets(string|array $entries, ?string $nonce = null): string
    {
        return static::getManager()->assets($entries, $nonce);
    }

    /**
     * Resolve a public URL for a static asset or build artifact.
     */
    public static function asset(string $path): string
    {
        return static::getManager()->asset($path);
    }

    /**
     * Check if Vite dev server is running.
     */
    public static function isRunning(): bool
    {
        return static::getManager()->isDev();
    }

    /**
     * Get the Vite development server URL.
     */
    public static function devServerUrl(?string $path = null): string
    {
        return static::getManager()->devServerUrl($path);
    }

    /**
     * Render CSRF meta tag for frontend API authentication.
     */
    public static function csrf(): string
    {
        $token = function_exists('session') && session()->has('_token') ? session()->get('_token') : (defined('CNA_CSRF_TOKEN') ? CNA_CSRF_TOKEN : bin2hex(random_bytes(16)));
        $renderer = new AssetTagRenderer();
        return $renderer->renderCsrfMetaTag((string) $token);
    }
}
