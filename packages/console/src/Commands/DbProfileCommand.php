<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Monitoring\QueryObserver;

class DbProfileCommand extends Command
{
    protected string $name = 'db:profile';
    protected string $description = 'Inspect query logs and detect potential N+1 query patterns';

    public function handle(): int
    {
        /** @var QueryObserver $observer */
        $observer = $this->app->has(QueryObserver::class) ? $this->app->get(QueryObserver::class) : null;
        if ($observer === null) {
            $this->comment("QueryObserver is not attached to this application runtime.");
            return Command::SUCCESS;
        }

        $totalQueries = $observer->getQueryCount();
        $totalDuration = $observer->getTotalDuration();
        $nPlusOne = $observer->detectNPlusOneQueries();

        $this->info("Database Query Profiler Summary");
        $this->table(['Metric', 'Value'], [
            ['Total Queries Executed', (string) $totalQueries],
            ['Total Execution Time', number_format($totalDuration * 1000, 2) . ' ms'],
            ['N+1 Candidates Flagged', (string) count($nPlusOne)],
        ]);

        if (!empty($nPlusOne)) {
            $this->warn("\nPotential N+1 Query Patterns Detected:");
            foreach ($nPlusOne as $issue) {
                $this->line(" - \033[33mCount:\033[0m {$issue['count']}x");
                $this->line("   \033[33mCall site:\033[0m {$issue['call_site']}");
                $this->line("   \033[33mSQL:\033[0m {$issue['sample_sql']}");
                $this->line("   \033[32mFix:\033[0m {$issue['recommendation']}\n");
            }
        }

        return Command::SUCCESS;
    }
}

