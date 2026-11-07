<?php

declare(strict_types=1);

namespace CnA\Queue;

use CnA\Contracts\Queue\JobInterface;
use CnA\Contracts\Queue\QueueInterface;

class Worker
{
    protected bool $shouldQuit = false;

    public function __construct(protected QueueInterface $queue)
    {
    }

    public function processNextJob(?string $queueName = null): bool
    {
        $job = $this->queue->pop($queueName);
        if ($job === null) {
            return false;
        }

        $this->process($job);
        return true;
    }

    public function process(JobInterface $job): void
    {
        try {
            $job->fire();
            $job->delete();
        } catch (\Throwable $e) {
            $this->handleJobException($job, $e);
        }
    }

    public function stop(): void
    {
        $this->shouldQuit = true;
    }

    protected function handleJobException(JobInterface $job, \Throwable $e): void
    {
        if ($job->attempts() >= $job->maxAttempts()) {
            $job->failed($e);
        } else {
            // Exponential backoff
            $delay = (int) (pow(2, $job->attempts()) * 5);
            $job->release($delay);
        }
    }
}

