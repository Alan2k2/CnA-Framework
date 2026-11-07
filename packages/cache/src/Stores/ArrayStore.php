<?php

declare(strict_types=1);

namespace CnA\Cache\Stores;

use CnA\Cache\Lock;
use CnA\Contracts\Cache\CacheInterface;
use CnA\Contracts\Cache\LockInterface;

class ArrayStore implements CacheInterface
{
    /** @var array<string, array{value: mixed, expires: ?int}> */
    protected array $storage = [];

    /** @var array<string, array<string>> */
    protected array $tags = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($this->storage[$key])) {
            return $default;
        }

        $item = $this->storage[$key];
        if ($item['expires'] !== null && time() >= $item['expires']) {
            unset($this->storage[$key]);
            return $default;
        }

        return $item['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $expires = $ttl !== null ? time() + $ttl : null;
        $this->storage[$key] = [
            'value' => $value,
            'expires' => $expires,
        ];
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->storage[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->storage = [];
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
}

