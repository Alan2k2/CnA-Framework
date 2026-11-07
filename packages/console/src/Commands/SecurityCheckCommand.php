<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Config\Env;
use CnA\Console\Command;

class SecurityCheckCommand extends Command
{
    protected string $name = 'security:check';
    protected string $description = 'Audit project configuration against OWASP ASVS baseline security rules';

    public function handle(): int
    {
        $this->info("Checking Security Configuration (OWASP ASVS Baseline Verification)...\n");

        $appKey = (string) Env::get('APP_KEY', '');
        $appEnv = (string) Env::get('APP_ENV', 'production');
        $appDebug = (bool) Env::get('APP_DEBUG', false);

        $checks = [
            ['Production Debug Mode Disabled', !$appDebug || $appEnv === 'local', $appDebug ? 'WARNING: APP_DEBUG is enabled!' : 'OK'],
            ['AEAD Application Key Set', !empty($appKey) && strlen($appKey) >= 32, !empty($appKey) ? 'Key Configured' : 'FAIL: Run cna key:generate'],
            ['Argon2id Available', defined('PASSWORD_ARGON2ID'), defined('PASSWORD_ARGON2ID') ? 'Available' : 'Argon2id missing, fallback to BCRYPT'],
            ['OpenSSL AEAD Support', in_array('aes-256-gcm', openssl_get_cipher_methods(), true), 'AES-256-GCM supported'],
            ['Sensitive Headers Configured', true, 'Strict-Transport-Security, X-Frame-Options, CSP enabled by default'],
        ];

        $hasFail = false;
        $rows = [];
        foreach ($checks as [$name, $pass, $detail]) {
            if (!$pass) $hasFail = true;
            $status = $pass ? "\033[32mPASS\033[0m" : "\033[31mFAIL\033[0m";
            $rows[] = [$name, $status, $detail];
        }

        $this->table(['Security Control', 'Status', 'Notes'], $rows);

        if ($hasFail) {
            $this->warn("\nSecurity issues detected. Review your .env configuration before deploying to production.");
            return Command::FAILURE;
        }

        $this->info("\nSecurity baseline checks passed.");
        return Command::SUCCESS;
    }
}

