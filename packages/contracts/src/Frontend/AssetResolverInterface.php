<?php

declare(strict_types=1);

namespace CnA\Contracts\Frontend;

interface AssetResolverInterface
{
    /**
     * Resolve all output files (main script, imported CSS, modulepreloads) for an entry point.
     *
     * @param string $entry Logical entry name or source path
     * @return array{file: string, css: array<int, string>, preloads: array<int, string>}
     */
    public function resolveEntry(string $entry): array;

    /**
     * Map a relative asset path to a safe public URL, preventing path traversal attacks.
     *
     * @param string $path
     * @return string
     */
    public function asset(string $path): string;
}
