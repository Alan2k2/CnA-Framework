<?php

declare(strict_types=1);

namespace CnA\Frontend;

use CnA\Contracts\Frontend\AssetResolverInterface;
use CnA\Contracts\Frontend\ManifestReaderInterface;
use InvalidArgumentException;
use RuntimeException;

class AssetResolver implements AssetResolverInterface
{
    /**
     * @param ManifestReaderInterface $manifest
     * @param string $buildDirectory Full filesystem path to build output (e.g. /public/build)
     * @param string $publicPath URL prefix (e.g. /build/)
     * @param array<string, string> $entryPoints Registered logical entry mappings
     */
    public function __construct(
        protected ManifestReaderInterface $manifest,
        protected string $buildDirectory,
        protected string $publicPath = '/build/',
        protected array $entryPoints = []
    ) {
        $this->publicPath = '/' . trim($publicPath, '/') . '/';
    }

    public function resolveEntry(string $entry): array
    {
        // 1. Resolve logical entry point through explicit registry if configured
        $sourcePath = $this->entryPoints[$entry] ?? $entry;

        // Security check: reject path traversal in entry keys
        $this->assertSafePath($sourcePath);

        // 2. Look up entry in manifest
        $record = $this->manifest->get($sourcePath);
        if ($record === null) {
            throw new RuntimeException("Asset entry [{$entry}] (source: [{$sourcePath}]) not found in manifest.");
        }

        $mainFile = $this->publicPath . ltrim($record['file'], '/');
        $this->assertWithinBuildRoot($record['file']);

        $css = [];
        $preloads = [];

        // Direct CSS
        if (!empty($record['css'])) {
            foreach ($record['css'] as $cssFile) {
                $this->assertWithinBuildRoot($cssFile);
                $css[] = $this->publicPath . ltrim($cssFile, '/');
            }
        }

        // 3. Recursively traverse static imports for modulepreload and CSS
        $visited = [];
        $this->collectImports($record, $css, $preloads, $visited);

        return [
            'file' => $mainFile,
            'css' => array_values(array_unique($css)),
            'preloads' => array_values(array_unique($preloads)),
        ];
    }

    public function asset(string $path): string
    {
        $this->assertSafePath($path);

        // Check if path is in manifest
        $record = $this->manifest->get($path);
        if ($record !== null) {
            $this->assertWithinBuildRoot($record['file']);
            return $this->publicPath . ltrim($record['file'], '/');
        }

        $this->assertWithinBuildRoot($path);
        return $this->publicPath . ltrim($path, '/');
    }

    /**
     * Recursively collect static imports and their associated stylesheets.
     */
    protected function collectImports(array $record, array &$css, array &$preloads, array &$visited): void
    {
        if (empty($record['imports'])) {
            return;
        }

        foreach ($record['imports'] as $importKey) {
            if (isset($visited[$importKey])) {
                continue;
            }
            $visited[$importKey] = true;

            $importRecord = $this->manifest->get($importKey);
            if ($importRecord === null) {
                continue;
            }

            $this->assertWithinBuildRoot($importRecord['file']);
            $preloads[] = $this->publicPath . ltrim($importRecord['file'], '/');

            if (!empty($importRecord['css'])) {
                foreach ($importRecord['css'] as $cssFile) {
                    $this->assertWithinBuildRoot($cssFile);
                    $css[] = $this->publicPath . ltrim($cssFile, '/');
                }
            }

            $this->collectImports($importRecord, $css, $preloads, $visited);
        }
    }

    /**
     * Path traversal security guard: normalize and reject traversal attempts.
     */
    protected function assertSafePath(string $path): void
    {
        if (str_contains($path, '..') || str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[a-zA-Z]:/', $path)) {
            throw new InvalidArgumentException("Path traversal attempt detected in asset reference [{$path}].");
        }
    }

    /**
     * Verify that resolved file stays strictly within the build directory.
     */
    protected function assertWithinBuildRoot(string $relativePath): void
    {
        $this->assertSafePath($relativePath);

        $fullPath = rtrim($this->buildDirectory, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (file_exists($fullPath)) {
            $realTarget = realpath($fullPath);
            $realRoot = realpath($this->buildDirectory);
            if ($realTarget !== false && $realRoot !== false && !str_starts_with($realTarget, $realRoot)) {
                throw new InvalidArgumentException("Symlink escape detected: asset [{$relativePath}] escapes build root.");
            }
        }
    }
}

