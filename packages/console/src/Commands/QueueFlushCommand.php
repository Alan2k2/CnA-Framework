<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Contracts\Database\ConnectionInterface;

class QueueFlushCommand extends Command
{
    protected string $name = 'queue:flush';
    protected string $description = 'Flush all of the failed queue jobs';

    public function handle(): int
    {
        /** @var ConnectionInterface $conn */
        $conn = $this->app->get(ConnectionInterface::class);

        try {
            $count = $conn->table('failed_jobs')->delete();
            $this->info("Deleted {$count} failed job(s) from the failed_jobs table.");
        } catch (\Throwable $e) {
            $this->comment("No failed_jobs table or table already empty.");
        }

        return Command::SUCCESS;
    }
}
