<?php

declare(strict_types=1);

namespace CnA\Storage;

use CnA\Contracts\Storage\StorageInterface;
use CnA\Storage\Disks\LocalDisk;

class StorageManager implements StorageInterface
{
    /** @var array<string, StorageInterface> */
    protected array $disks = [];

    public function __construct(
        protected array $config = [],
        protected string $default = 'local'
    ) {
    }

    public function disk(?string $name = null): StorageInterface
    {
        $name = $name ?? $this->default;
        if (!isset($this->disks[$name])) {
            $this->disks[$name] = $this->createDisk($name);
        }
        return $this->disks[$name];
    }

    protected function createDisk(string $name): StorageInterface
    {
        $cfg = $this->config['disks'][$name] ?? [];
        $driver = $cfg['driver'] ?? 'local';

        return match ($driver) {
            'local' => new LocalDisk($cfg['root'] ?? sys_get_temp_dir() . '/cna_storage', $cfg['url'] ?? '/storage'),
            default => new LocalDisk(sys_get_temp_dir() . '/cna_storage'),
        };
    }

    public function get(string $path): ?string
    {
        return $this->disk()->get($path);
    }

    public function put(string $path, string $contents, array $options = []): bool
    {
        return $this->disk()->put($path, $contents, $options);
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    public function delete(string|array $paths): bool
    {
        return $this->disk()->delete($paths);
    }

    public function size(string $path): int
    {
        return $this->disk()->size($path);
    }

    public function lastModified(string $path): int
    {
        return $this->disk()->lastModified($path);
    }

    public function mimeType(string $path): ?string
    {
        return $this->disk()->mimeType($path);
    }

    public function url(string $path): string
    {
        return $this->disk()->url($path);
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): string
    {
        return $this->disk()->temporaryUrl($path, $expiration, $options);
    }
}

