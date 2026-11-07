<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Contracts\Queue\QueueInterface;
use CnA\Queue\Worker;

class QueueWorkCommand extends Command
{
    protected string $name = 'queue:work';
    protected string $description = 'Start processing jobs on the queue as a daemon';

    public function handle(): int
    {
        $queueName = $this->argument(0, 'default');
        $once = $this->hasOption('once');

        /** @var QueueInterface $queue */
        $queue = $this->app->get(QueueInterface::class);
        $worker = new Worker($queue);

        $this->info("Processing jobs on queue [{$queueName}]...");

        if ($once) {
            $processed = $worker->processNextJob($queueName);
            if ($processed) {
                $this->info("Job processed.");
            } else {
                $this->comment("No jobs in queue.");
            }
            return Command::SUCCESS;
        }

        $maxLoops = (int) $this->option('max-jobs', 100);
        $count = 0;

        while ($count < $maxLoops) {
            $processed = $worker->processNextJob($queueName);
            if ($processed) {
                $count++;
            } else {
                usleep(500000); // 0.5s sleep when idle
                break;
            }
        }

        $this->info("Processed {$count} jobs.");
        return Command::SUCCESS;
    }
}

