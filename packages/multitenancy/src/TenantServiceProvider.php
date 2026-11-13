<?php

declare(strict_types=1);

namespace CnA\Tenancy;

use CnA\Contracts\Tenancy\TenantManagerInterface;
use CnA\Core\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantManagerInterface::class, function () {
            $manager = new TenantManager();

            $tenants = (array) $this->app->make('config')->get('tenancy.tenants', []);
            foreach ($tenants as $id => $data) {
                $tenant = new Tenant(
                    $id,
                    $data['name'] ?? $id,
                    $data['domain'] ?? null,
                    $data['database'] ?? null
                );
                $manager->register($tenant);
            }

            return $manager;
        });

        $this->app->alias(TenantManagerInterface::class, TenantManager::class);
        $this->app->alias(TenantManagerInterface::class, 'tenancy');
    }

    public function boot(): void
    {
    }
}

