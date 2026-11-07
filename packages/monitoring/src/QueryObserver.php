<?php

declare(strict_types=1);

namespace CnA\Monitoring;

use CnA\Contracts\Exceptions\QueryBudgetExceededException;
use CnA\Database\Connection;

class QueryObserver
{
    /** @var array<array{sql: string, fingerprint: string, bindings: array, duration: float, connection: string, call_site: string}> */
    protected array $queries = [];

    /** @var array<string, int> */
    protected array $fingerprintCounts = [];

    protected ?int $queryBudget = null;
    protected float $slowQueryThreshold = 0.5; // 500ms
    protected bool $enabled = true;

    public function __construct()
    {
        Connection::listen(function (array $event) {
            if ($this->enabled) {
                $this->recordQuery($event);
            }
        });
    }

    public function setQueryBudget(?int $budget): static
    {
        $this->queryBudget = $budget;
        return $this;
    }

    public function setSlowQueryThreshold(float $seconds): static
    {
        $this->slowQueryThreshold = $seconds;
        return $this;
    }

    public function recordQuery(array $event): void
    {
        $sql = $event['sql'];
        $fingerprint = QueryNormalizer::fingerprint($sql);
        $bindings = QueryNormalizer::redactBindings($event['bindings'] ?? []);
        $duration = (float) ($event['duration'] ?? 0.0);
        $connection = $event['connection'] ?? 'default';

        $callSite = $this->resolveCallSite();

        $this->queries[] = [
            'sql' => $sql,
            'fingerprint' => $fingerprint,
            'bindings' => $bindings,
            'duration' => $duration,
            'connection' => $connection,
            'call_site' => $callSite,
        ];

        $this->fingerprintCounts[$fingerprint] = ($this->fingerprintCounts[$fingerprint] ?? 0) + 1;

        if ($this->queryBudget !== null && count($this->queries) > $this->queryBudget) {
            throw new QueryBudgetExceededException($this->queryBudget, count($this->queries));
        }
    }

    public function getQueries(): array
    {
        return $this->queries;
    }

    public function getQueryCount(): int
    {
        return count($this->queries);
    }

    public function getTotalDuration(): float
    {
        return array_sum(array_column($this->queries, 'duration'));
    }

    public function getSlowQueries(): array
    {
        return array_filter($this->queries, fn($q) => $q['duration'] >= $this->slowQueryThreshold);
    }

    /**
     * Detect queries that repeat frequently with different parameters (N+1 candidate pattern).
     */
    public function detectNPlusOneQueries(int $threshold = 2): array
    {
        $suspects = [];

        foreach ($this->fingerprintCounts as $fp => $count) {
            if ($count >= $threshold) {
                $matching = array_values(array_filter($this->queries, fn($q) => $q['fingerprint'] === $fp));
                $sample = $matching[0] ?? null;

                $suspects[] = [
                    'fingerprint' => $fp,
                    'count' => $count,
                    'call_site' => $sample['call_site'] ?? 'Unknown',
                    'sample_sql' => $sample['sql'] ?? '',
                    'recommendation' => "Query was executed {$count} times. Consider using eager loading `with(['relation'])` or batching queries to reduce round-trips.",
                ];
            }
        }

        return $suspects;
    }

    public function reset(): void
    {
        $this->queries = [];
        $this->fingerprintCounts = [];
    }

    protected function resolveCallSite(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';
            // Skip database internal files
            if (
                $file !== '' &&
                !str_contains($file, 'packages/database') &&
                !str_contains($file, 'packages/query-builder') &&
                !str_contains($file, 'packages/monitoring')
            ) {
                return basename($file) . ':' . ($frame['line'] ?? 0);
            }
        }
        return 'unknown';
    }
}

