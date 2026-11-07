<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Ai\AiManager;
use CnA\Console\Command;

class AiUsageCommand extends Command
{
    protected string $name = 'ai:usage';
    protected string $description = 'Report token consumption and tenant quota usage';

    public function handle(): int
    {
        /** @var AiManager $ai */
        $ai = $this->app->has(AiManager::class) ? $this->app->get(AiManager::class) : null;
        if ($ai === null) {
            $this->comment("AI subsystem not active.");
            return Command::SUCCESS;
        }

        $usages = $ai->costTracker()->getAllUsage();
        if (empty($usages)) {
            $this->comment("No AI token usage recorded yet.");
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($usages as $tenant => $tokens) {
            $rows[] = [$tenant, number_format($tokens)];
        }

        $this->table(['Tenant ID', 'Tokens Used'], $rows);
        return Command::SUCCESS;
    }
}

