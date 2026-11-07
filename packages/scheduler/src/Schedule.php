<?php

declare(strict_types=1);

namespace CnA\Scheduler;

use CnA\Contracts\Cache\CacheInterface;

class Schedule
{
    /** @var array<ScheduledEvent> */
    protected array $events = [];

    public function __construct(protected ?CacheInterface $cache = null)
    {
    }

    public function call(callable $callback): ScheduledEvent
    {
        $event = new ScheduledEvent($callback);
        $this->events[] = $event;
        return $event;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function dueEvents(?\DateTimeInterface $date = null): array
    {
        return array_filter($this->events, fn(ScheduledEvent $e) => $e->isDue($date));
    }

    public function runDueEvents(?\DateTimeInterface $date = null): int
    {
        $due = $this->dueEvents($date);
        $ran = 0;

        foreach ($due as $event) {
            $event->run($this->cache);
            $ran++;
        }

        return $ran;
    }
}

