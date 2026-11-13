<?php

declare(strict_types=1);

namespace CnA\Contracts\Frontend;

interface PreloadManagerInterface
{
    /**
     * Add a modulepreload URL with deduplication.
     *
     * @param string $url
     * @return void
     */
    public function addPreload(string $url): void;

    /**
     * Get all collected preload URLs for the current request.
     *
     * @return array<int, string>
     */
    public function getPreloads(): array;

    /**
     * Add a stylesheet URL with deduplication.
     *
     * @param string $url
     * @return void
     */
    public function addStylesheet(string $url): void;

    /**
     * Get all collected stylesheet URLs for the current request.
     *
     * @return array<int, string>
     */
    public function getStylesheets(): array;

    /**
     * Reset deduplication caches for a new response.
     *
     * @return void
     */
    public function clear(): void;
}
