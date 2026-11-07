<?php

declare(strict_types=1);

namespace CnA\Scheduler;

use CnA\Contracts\Cache\CacheInterface;

class ScheduledEvent
{
    /** @var callable|string */
    protected mixed $callback;
    protected string $expression = '* * * * *';
    protected bool $withoutOverlapping = false;
    protected string $description = '';

    public function __construct(callable|string $callback)
    {
        $this->callback = $callback;
    }

    public function cron(string $expression): static
    {
        $this->expression = $expression;
        return $this;
    }

    public function everyMinute(): static
    {
        return $this->cron('* * * * *');
    }

    public function hourly(): static
    {
        return $this->cron('0 * * * *');
    }

    public function daily(): static
    {
        return $this->cron('0 0 * * *');
    }

    public function withoutOverlapping(): static
    {
        $this->withoutOverlapping = true;
        return $this;
    }

    public function description(string $desc): static
    {
        $this->description = $desc;
        return $this;
    }

    public function isDue(?\DateTimeInterface $date = null): bool
    {
        $date = $date ?? new \DateTimeImmutable();
        $parts = explode(' ', $this->expression);
        if (count($parts) !== 5) {
            return false;
        }

        [$min, $hour, $day, $month, $weekday] = $parts;

        return $this->matchCronPart($min, (int) $date->format('i'))
            && $this->matchCronPart($hour, (int) $date->format('H'))
            && $this->matchCronPart($day, (int) $date->format('d'))
            && $this->matchCronPart($month, (int) $date->format('m'))
            && $this->matchCronPart($weekday, (int) $date->format('w'));
    }

    public function run(?CacheInterface $cache = null): mixed
    {
        if ($this->withoutOverlapping && $cache !== null) {
            $lock = $cache->lock('scheduler_lock_' . md5($this->expression . $this->description), 300);
            return $lock->get(fn() => $this->execute());
        }

        return $this->execute();
    }

    protected function execute(): mixed
    {
        if (is_callable($this->callback)) {
            return ($this->callback)();
        }
        return null;
    }

    protected function matchCronPart(string $part, int $current): bool
    {
        if ($part === '*') {
            return true;
        }
        if (str_starts_with($part, '*/')) {
            $step = (int) substr($part, 2);
            return $step > 0 && ($current % $step === 0);
        }
        return (int) $part === $current;
    }
}

