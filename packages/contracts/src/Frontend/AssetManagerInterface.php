<?php

declare(strict_types=1);

namespace CnA\Contracts\Frontend;

interface AssetManagerInterface
{
    /**
     * Render HTML script, stylesheet, and modulepreload tags for one or more entries.
     *
     * @param string|array<int, string> $entries
     * @param string|null $nonce Optional CSP nonce
     * @return string
     */
    public function assets(string|array $entries, ?string $nonce = null): string;

    /**
     * Determine if the frontend Vite development server is running and active.
     *
     * @return bool
     */
    public function isDev(): bool;

    /**
     * Get the Vite development server base URL or URL for a specific asset path.
     *
     * @param string|null $path
     * @return string
     */
    public function devServerUrl(?string $path = null): string;

    /**
     * Resolve public URL and dependencies for a registered entry point.
     *
     * @param string $entry
     * @return array{file: string, css: array<string>, imports: array<string>, isEntry?: bool}
     */
    public function resolve(string $entry): array;

    /**
     * Get the public asset URL for a static file or build artifact.
     *
     * @param string $path
     * @return string
     */
    public function asset(string $path): string;
}

