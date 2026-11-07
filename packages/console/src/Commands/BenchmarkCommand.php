<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class BenchmarkCommand extends Command
{
    protected string $name = 'benchmark';
    protected string $description = 'Run framework micro-benchmarks for DI, routing, and ORM';

    public function handle(array $arguments = []): int
    {
        $benchmarkScript = $this->app->basePath('benchmarks/run.php');
        if (!file_exists($benchmarkScript)) {
            $this->error("Benchmark script not found at {$benchmarkScript}");
            return 1;
        }

        require $benchmarkScript;
        return 0;
    }
}

