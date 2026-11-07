<?php

declare(strict_types=1);

namespace CnA\Events;

use CnA\Contracts\Container\ContainerInterface;
use CnA\Contracts\Events\EventDispatcherInterface;

class EventDispatcher implements EventDispatcherInterface
{
    /** @var array<string, array<callable|string|array>> */
    protected array $listeners = [];

    public function __construct(protected ?ContainerInterface $container = null)
    {
    }

    public function dispatch(object $event): object
    {
        $eventName = get_class($event);
        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                $this->executeListener($listener, $event);
            }
        }

        return $event;
    }

    public function listen(string $event, callable|string|array $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function subscribe(object|string $subscriber): void
    {
        $instance = is_string($subscriber)
            ? ($this->container !== null ? $this->container->get($subscriber) : new $subscriber())
            : $subscriber;

        if (method_exists($instance, 'subscribe')) {
            $instance->subscribe($this);
        }
    }

    public function hasListeners(string $event): bool
    {
        return !empty($this->listeners[$event]);
    }

    protected function executeListener(callable|string|array $listener, object $event): void
    {
        if (is_callable($listener)) {
            $listener($event);
            return;
        }

        if (is_string($listener)) {
            $instance = $this->container !== null ? $this->container->get($listener) : new $listener();
            if (method_exists($instance, 'handle')) {
                $instance->handle($event);
            }
            return;
        }

        if (is_array($listener) && count($listener) === 2) {
            [$class, $method] = $listener;
            $instance = is_string($class)
                ? ($this->container !== null ? $this->container->get($class) : new $class())
                : $class;
            $instance->{$method}($event);
        }
    }
}

