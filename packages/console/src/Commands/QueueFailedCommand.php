<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Contracts\Database\ConnectionInterface;

class QueueFailedCommand extends Command
{
    protected string $name = 'queue:failed';
    protected string $description = 'List all of the failed queue jobs';

    public function handle(): int
    {
        /** @var ConnectionInterface $conn */
        $conn = $this->app->get(ConnectionInterface::class);

        try {
            $failed = $conn->table('failed_jobs')->orderBy('id', 'desc')->get();
        } catch (\Throwable) {
            $this->comment("No failed_jobs table or no failed jobs.");
            return Command::SUCCESS;
        }

        if (empty($failed)) {
            $this->info("No failed jobs found in queue.");
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($failed as $job) {
            $payload = json_decode($job['payload'], true);
            $jobName = $payload['job'] ?? 'Unknown';
            $exceptionLine = explode("\n", $job['exception'])[0] ?? '';
            $rows[] = [$job['id'], $job['connection'], $job['queue'], $jobName, substr($exceptionLine, 0, 40), $job['failed_at']];
        }

        $this->table(['ID', 'Connection', 'Queue', 'Class', 'Exception', 'Failed At'], $rows);
        return Command::SUCCESS;
    }
}

