<?php

declare(strict_types=1);

namespace CnA\Cache\Stores;

use CnA\Cache\Lock;
use CnA\Contracts\Cache\CacheInterface;
use CnA\Contracts\Cache\LockInterface;

class FileStore implements CacheInterface
{
    public function __construct(protected string $directory)
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->path($key);
        if (!file_exists($path)) {
            return $default;
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            return $default;
        }

        $data = unserialize($contents);
        if ($data['expires'] !== null && time() >= $data['expires']) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $path = $this->path($key);
        $expires = $ttl !== null ? time() + $ttl : null;

        $payload = serialize([
            'value' => $value,
            'expires' => $expires,
        ]);

        return (bool) file_put_contents($path, $payload, LOCK_EX);
    }

    public function delete(string $key): bool
    {
        $path = $this->path($key);
        if (file_exists($path)) {
            return @unlink($path);
        }
        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->directory . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        $val = $this->get($key);
        if ($val !== null) {
            return $val;
        }

        $val = $callback();
        $this->set($key, $val, $ttl);
        return $val;
    }

    public function lock(string $name, int $seconds = 0): LockInterface
    {
        return new Lock($this, $name, $seconds);
    }

    public function tags(array|string $names): static
    {
        return $this;
    }

    protected function path(string $key): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . sha1($key);
    }
}

