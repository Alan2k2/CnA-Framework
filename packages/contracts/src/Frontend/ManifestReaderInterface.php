<?php

declare(strict_types=1);

namespace CnA\Contracts\Frontend;

interface ManifestReaderInterface
{
    /**
     * Load, validate, and return the decoded production build manifest.
     *
     * @return array<string, array{file: string, src?: string, isEntry?: bool, css?: array<string>, assets?: array<string>, imports?: array<string>, dynamicImports?: array<string>}>
     */
    public function load(): array;

    /**
     * Get manifest entry metadata by logical key or source path.
     *
     * @param string $entry
     * @return array{file: string, src?: string, isEntry?: bool, css?: array<string>, assets?: array<string>, imports?: array<string>, dynamicImports?: array<string>}|null
     */
    public function get(string $entry): ?array;

    /**
     * Determine if a given entry exists in the manifest.
     *
     * @param string $entry
     * @return bool
     */
    public function has(string $entry): bool;

    /**
     * Clear cached manifest memory.
     *
     * @return void
     */
    public function clear(): void;
}

