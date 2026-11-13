<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Security\SecurityAuditor;

class SecurityReportCommand extends Command
{
    protected string $name = 'security:report';
    protected string $description = 'Generate full security audit report in structured JSON or text format';

    public function handle(): int
    {
        $auditor = new SecurityAuditor($this->app->basePath());
        $format = $this->option('format') ?? 'json';

        if ($format === 'json') {
            $this->line($auditor->toJson());
            return Command::SUCCESS;
        }

        $audit = $auditor->runFullAudit();
        $this->info("CnA Framework Security Audit Report - " . $audit['timestamp']);
        $this->info("Overall Status: " . $audit['status']);
        $this->info("Summary:");
        $this->info("  Dependencies: " . $audit['summary']['dependencies']);
        $this->info("  Permissions:  " . $audit['summary']['permissions']);
        $this->info("  Headers:      " . $audit['summary']['headers']);

        return Command::SUCCESS;
    }
}
