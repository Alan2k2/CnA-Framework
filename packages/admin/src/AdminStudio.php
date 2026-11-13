<?php

declare(strict_types=1);

namespace CnA\Admin;

use CnA\Contracts\Core\ApplicationInterface;

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

        return [
            'version' => defined('CNA_VERSION') ? CNA_VERSION : '2.1.0',
            'php_version' => $phpVersion,
            'memory_usage_bytes' => $memoryUsage,
            'memory_peak_bytes' => $peakMemory,
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
