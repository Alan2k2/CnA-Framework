<?php

declare(strict_types=1);

namespace CnA\Cache;

use CnA\Cache\Stores\ArrayStore;
use CnA\Cache\Stores\FileStore;
use CnA\Contracts\Cache\CacheInterface;
use CnA\Contracts\Cache\LockInterface;

class CacheManager implements CacheInterface
{
    /** @var array<string, CacheInterface> */
    protected array $stores = [];

    public function __construct(
        protected array $config = [],
        protected string $default = 'array'
    ) {
    }

    public function store(?string $name = null): CacheInterface
    {
        $name = $name ?? $this->default;
        if (!isset($this->stores[$name])) {
            $this->stores[$name] = $this->createStore($name);
        }
        return $this->stores[$name];
    }

    protected function createStore(string $name): CacheInterface
    {
        $driver = $this->config['stores'][$name]['driver'] ?? $name;

        return match ($driver) {
            'array' => new ArrayStore(),
            'file' => new FileStore($this->config['stores'][$name]['path'] ?? sys_get_temp_dir() . '/cna_cache'),
            default => new ArrayStore(),
        };
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store()->get($key, $default);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->store()->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->store()->delete($key);
    }

    public function clear(): bool
    {
        return $this->store()->clear();
    }

    public function has(string $key): bool
    {
        return $this->store()->has($key);
    }

    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        return $this->store()->remember($key, $ttl, $callback);
    }

    public function lock(string $name, int $seconds = 0): LockInterface
    {
        return $this->store()->lock($name, $seconds);
    }

    public function tags(array|string $names): static
    {
        $this->store()->tags($names);
        return $this;
    }
}

