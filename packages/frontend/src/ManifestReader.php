<?php

declare(strict_types=1);

namespace CnA\Frontend;

use CnA\Contracts\Frontend\ManifestReaderInterface;
use RuntimeException;

class ManifestReader implements ManifestReaderInterface
{
    /** @var array<string, mixed>|null */
    protected ?array $cachedManifest = null;
    protected ?int $cachedMtime = null;

    /**
     * @param string $manifestPath Path to the manifest file
     * @param string|null $fallbackPath Alternative path (e.g. .vite/manifest.json vs manifest.json)
     */
    public function __construct(
        protected string $manifestPath,
        protected ?string $fallbackPath = null
    ) {
    }

    public function load(): array
    {
        $path = $this->resolvePath();
        if ($path === null) {
            throw new RuntimeException("Vite manifest not found. Looked in [{$this->manifestPath}]" . ($this->fallbackPath ? " and [{$this->fallbackPath}]" : '') . ". Run `php cna frontend:build` or `npm run build`.");
        }

        $mtime = filemtime($path);
        if ($this->cachedManifest !== null && $this->cachedMtime === $mtime) {
            return $this->cachedManifest;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException("Failed to read Vite manifest at [{$path}].");
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException("Vite manifest at [{$path}] contains invalid JSON.");
        }

        // Schema validation: verify entries contain required 'file' string
        foreach ($data as $key => $entry) {
            if (!is_array($entry) || !isset($entry['file']) || !is_string($entry['file'])) {
                throw new RuntimeException("Vite manifest entry [{$key}] is malformed. Expected 'file' property.");
            }
        }

        $this->cachedManifest = $data;
        $this->cachedMtime = $mtime;

        return $this->cachedManifest;
    }

    public function get(string $entry): ?array
    {
        $manifest = $this->load();

        if (isset($manifest[$entry])) {
            return $manifest[$entry];
        }

        // Check if an entry has a matching 'src' property
        foreach ($manifest as $manifestKey => $item) {
            if (($item['src'] ?? null) === $entry || $manifestKey === $entry) {
                return $item;
            }
        }

        return null;
    }

    public function has(string $entry): bool
    {
        return $this->get($entry) !== null;
    }

    public function clear(): void
    {
        $this->cachedManifest = null;
        $this->cachedMtime = null;
    }

    public function resolvePath(): ?string
    {
        if (file_exists($this->manifestPath)) {
            return $this->manifestPath;
        }

        if ($this->fallbackPath !== null && file_exists($this->fallbackPath)) {
            return $this->fallbackPath;
        }

        return null;
    }
}
