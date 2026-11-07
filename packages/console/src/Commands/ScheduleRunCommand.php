<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Scheduler\Schedule;

class ScheduleRunCommand extends Command
{
    protected string $name = 'schedule:run';
    protected string $description = 'Run the scheduled tasks';

    public function handle(): int
    {
        /** @var Schedule $schedule */
        $schedule = $this->app->has(Schedule::class) ? $this->app->get(Schedule::class) : new Schedule();
        $ran = $schedule->runDueEvents();

        if ($ran === 0) {
            $this->comment("No scheduled tasks are due.");
        } else {
            $this->info("Ran {$ran} scheduled task(s).");
        }

        return Command::SUCCESS;
    }
}

