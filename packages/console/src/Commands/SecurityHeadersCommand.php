<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Security\SecurityAuditor;

class SecurityHeadersCommand extends Command
{
    protected string $name = 'security:headers';
    protected string $description = 'Audit HTTP security headers against OWASP ASVS recommendations';

    public function handle(): int
    {
        $this->info("Auditing HTTP security headers...\n");

        $auditor = new SecurityAuditor($this->app->basePath());
        $results = $auditor->auditHeaders([
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ]);

        $rows = [];
        foreach ($results['items'] as $item) {
            $status = $item['status'] === 'PASS' ? "\033[32mPASS\033[0m" : "\033[33mWARN\033[0m";
            $rows[] = [$item['header'], $status, $item['details']];
        }

        $this->table(['Security Header', 'Status', 'Recommendation / Status'], $rows);
        return Command::SUCCESS;
    }
}
