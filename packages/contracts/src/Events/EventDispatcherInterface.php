<?php

declare(strict_types=1);

namespace CnA\Contracts\Events;

interface EventDispatcherInterface
{
    public function dispatch(object $event): object;

    public function listen(string $event, callable|string|array $listener): void;

    public function subscribe(object|string $subscriber): void;

    public function hasListeners(string $event): bool;
}

