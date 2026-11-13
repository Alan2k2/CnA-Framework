<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Security\SecurityAuditor;

class SecurityAuditCommand extends Command
{
    protected string $name = 'security:audit';
    protected string $description = 'Perform deep security audit across dependencies, headers, and permissions';

    public function handle(): int
    {
        $this->info("Starting comprehensive security audit...\n");

        $auditor = new SecurityAuditor($this->app->basePath());
        $results = $auditor->runFullAudit();

        // 1. Dependencies
        $this->info("1. Dependency Audit:");
        $depRows = [];
        foreach ($results['dependencies']['items'] as $item) {
            $status = $item['status'] === 'PASS' ? "\033[32mPASS\033[0m" : ($item['status'] === 'WARN' ? "\033[33mWARN\033[0m" : "\033[31mFAIL\033[0m");
            $depRows[] = [$item['name'], $status, $item['details']];
        }
        $this->table(['Check', 'Status', 'Details'], $depRows);

        // 2. HTTP Security Headers
        $this->info("\n2. HTTP Security Headers Baseline:");
        $headerRows = [];
        foreach ($results['headers']['items'] as $item) {
            $status = $item['status'] === 'PASS' ? "\033[32mPASS\033[0m" : "\033[33mWARN\033[0m";
            $headerRows[] = [$item['header'], $status, $item['details']];
        }
        $this->table(['Header', 'Status', 'Details'], $headerRows);

        // 3. Filesystem Permissions
        $this->info("\n3. Filesystem & Environment Permissions:");
        $permRows = [];
        foreach ($results['permissions']['items'] as $item) {
            $status = $item['status'] === 'PASS' ? "\033[32mPASS\033[0m" : ($item['status'] === 'WARN' ? "\033[33mWARN\033[0m" : "\033[31mFAIL\033[0m");
            $permRows[] = [$item['path'], $status, $item['details']];
        }
        $this->table(['Path', 'Status', 'Details'], $permRows);

        if ($results['status'] === 'FAIL') {
            $this->error("\nAudit completed with critical failures. Please review the table above.");
            return Command::FAILURE;
        }

        $this->info("\nSecurity audit completed successfully.");
        return Command::SUCCESS;
    }
}
