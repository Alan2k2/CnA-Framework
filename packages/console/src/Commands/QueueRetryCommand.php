<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Contracts\Database\ConnectionInterface;
use CnA\Contracts\Queue\QueueInterface;

class QueueRetryCommand extends Command
{
    protected string $name = 'queue:retry';
    protected string $description = 'Retry a failed queue job by ID, or pass "all" to retry all failed jobs';

    public function handle(): int
    {
        $id = $this->argument(0);
        if (!$id) {
            $this->error('Please specify a job ID or "all" to retry.');
            return Command::INVALID;
        }

        /** @var ConnectionInterface $conn */
        $conn = $this->app->get(ConnectionInterface::class);
        /** @var QueueInterface $queue */
        $queue = $this->app->get(QueueInterface::class);

        try {
            $query = $conn->table('failed_jobs');
            if ($id !== 'all') {
                $query->where('id', (int) $id);
            }
            $failedJobs = $query->get();
        } catch (\Throwable $e) {
            $this->error("Failed to query failed_jobs: {$e->getMessage()}");
            return Command::FAILURE;
        }

        if (empty($failedJobs)) {
            $this->comment("No matching failed jobs found to retry.");
            return Command::SUCCESS;
        }

        $retried = 0;
        foreach ($failedJobs as $job) {
            $payload = json_decode($job['payload'], true);
            $jobClass = $payload['job'] ?? null;
            $data = isset($payload['data']) ? unserialize($payload['data']) : null;

            // Re-push to queue
            $queue->push($data ?? $jobClass, '', $job['queue'] ?? 'default');

            // Delete from failed_jobs
            $conn->table('failed_jobs')->where('id', $job['id'])->delete();
            $retried++;
        }

        $this->info("Successfully pushed {$retried} failed job(s) back onto the queue.");
        return Command::SUCCESS;
    }
}
