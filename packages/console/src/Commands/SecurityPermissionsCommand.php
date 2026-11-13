<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Security\SecurityAuditor;

class SecurityPermissionsCommand extends Command
{
    protected string $name = 'security:permissions';
    protected string $description = 'Audit filesystem permissions on sensitive paths and directories';

    public function handle(): int
    {
        $this->info("Auditing filesystem permissions...\n");

        $auditor = new SecurityAuditor($this->app->basePath());
        $results = $auditor->auditPermissions();

        $rows = [];
        foreach ($results['items'] as $item) {
            $status = $item['status'] === 'PASS' ? "\033[32mPASS\033[0m" : ($item['status'] === 'WARN' ? "\033[33mWARN\033[0m" : "\033[31mFAIL\033[0m");
            $rows[] = [$item['path'], $status, $item['details']];
        }

        $this->table(['Filesystem Path', 'Status', 'Details'], $rows);

        return $results['status'] === 'FAIL' ? Command::FAILURE : Command::SUCCESS;
    }
}

