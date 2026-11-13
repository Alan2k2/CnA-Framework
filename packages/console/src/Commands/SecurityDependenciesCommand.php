<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Security\SecurityAuditor;

class SecurityDependenciesCommand extends Command
{
    protected string $name = 'security:dependencies';
    protected string $description = 'Audit Composer dependencies, constraints, and lockfile integrity';

    public function handle(): int
    {
        $this->info("Auditing Composer dependencies...\n");

        $auditor = new SecurityAuditor($this->app->basePath());
        $results = $auditor->auditDependencies();

        $rows = [];
        foreach ($results['items'] as $item) {
            $status = $item['status'] === 'PASS' ? "\033[32mPASS\033[0m" : ($item['status'] === 'WARN' ? "\033[33mWARN\033[0m" : "\033[31mFAIL\033[0m");
            $rows[] = [$item['name'], $status, $item['details']];
        }

        $this->table(['Dependency Check', 'Status', 'Details'], $rows);

        return $results['status'] === 'FAIL' ? Command::FAILURE : Command::SUCCESS;
    }
}
