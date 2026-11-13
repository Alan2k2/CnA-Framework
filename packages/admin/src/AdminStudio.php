<?php

declare(strict_types=1);

namespace CnA\Admin;

use CnA\Contracts\Core\ApplicationInterface;
use CnA\Security\SecurityAuditor;

class AdminStudio
{
    public function __construct(protected ApplicationInterface $app)
    {
    }

    /**
     * Gather system diagnostic & telemetry status for Admin dashboard.
     *
     * @return array<string, mixed>
     */
    public function getDashboardSummary(): array
    {
        $phpVersion = PHP_VERSION;
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        $plugins = [];
        if ($this->app->has('plugins')) {
            $plugins = array_keys($this->app->make('plugins')->all());
        }

        $tenants = [];
        if ($this->app->has('tenancy')) {
            $tenants = array_keys($this->app->make('tenancy')->all());
        }

        $workflows = [];
        if ($this->app->has('workflow')) {
            $workflows = array_keys($this->app->make('workflow')->all());
        }

        $features = [];
        if ($this->app->has('features')) {
            $features = array_keys($this->app->make('features')->all());
        }

        $dbStatus = 'unconfigured';
        if ($this->app->has('db')) {
            try {
                $db = $this->app->make('db');
                if (method_exists($db, 'connection')) {
                    $db->connection()->select('SELECT 1');
                    $dbStatus = 'healthy';
                }
            } catch (\Throwable $e) {
                $dbStatus = 'degraded (' . $e->getMessage() . ')';
            }
        }

        $securityStatus = 'unknown';
        if (class_exists(SecurityAuditor::class)) {
            try {
                $auditor = new SecurityAuditor($this->app->basePath());
                $audit = $auditor->runFullAudit();
                $securityStatus = $audit['status'] ?? 'unknown';
            } catch (\Throwable) {
                $securityStatus = 'error';
            }
        }

        return [
            'app_name' => env('APP_NAME', 'CnA Framework'),
            'version' => defined('CNA_VERSION') ? CNA_VERSION : '2.1.0',
            'php_version' => $phpVersion,
            'environment' => env('APP_ENV', 'production'),
            'debug' => (bool) env('APP_DEBUG', false),
            'memory_usage_bytes' => $memoryUsage,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'memory_peak_bytes' => $peakMemory,
            'memory_peak_mb' => round($peakMemory / 1024 / 1024, 2),
            'database_status' => $dbStatus,
            'security_status' => $securityStatus,
            'plugins_count' => count($plugins),
            'plugins' => $plugins,
            'tenants_count' => count($tenants),
            'tenants' => $tenants,
            'workflows_count' => count($workflows),
            'workflows' => $workflows,
            'features_count' => count($features),
            'features' => $features,
        ];
    }
}
