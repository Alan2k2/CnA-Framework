<?php

declare(strict_types=1);

namespace CnA\Contracts\Cache;

interface LockInterface
{
    public function acquire(): bool;
    public function release(): bool;
    public function get(?callable $callback = null): mixed;
    public function block(int $seconds, ?callable $callback = null): mixed;
}

