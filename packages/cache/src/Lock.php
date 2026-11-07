<?php

declare(strict_types=1);

namespace CnA\Cache;

use CnA\Contracts\Cache\CacheInterface;
use CnA\Contracts\Cache\LockInterface;

class Lock implements LockInterface
{
    protected ?string $owner = null;

    public function __construct(
        protected CacheInterface $store,
        protected string $name,
        protected int $seconds = 0
    ) {
        $this->owner = bin2hex(random_bytes(16));
    }

    public function acquire(): bool
    {
        $key = 'lock:' . $this->name;
        if ($this->store->has($key)) {
            return false;
        }

        $ttl = $this->seconds > 0 ? $this->seconds : 3600;
        return $this->store->set($key, $this->owner, $ttl);
    }

    public function release(): bool
    {
        $key = 'lock:' . $this->name;
        $currentOwner = $this->store->get($key);

        if ($currentOwner === $this->owner) {
            return $this->store->delete($key);
        }

        return false;
    }

    public function get(?callable $callback = null): mixed
    {
        if ($this->acquire()) {
            try {
                return $callback !== null ? $callback() : true;
            } finally {
                $this->release();
            }
        }
        return false;
    }

    public function block(int $seconds, ?callable $callback = null): mixed
    {
        $start = time();
        while (!$this->acquire()) {
            if (time() - $start >= $seconds) {
                return false;
            }
            usleep(50000); // 50ms
        }

        try {
            return $callback !== null ? $callback() : true;
        } finally {
            $this->release();
        }
    }
}

