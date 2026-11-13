<?php

declare(strict_types=1);

namespace CnA\Admin;

use CnA\Contracts\Routing\RouterInterface;
use CnA\Core\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AdminStudio::class, function ($app) {
            return new AdminStudio($app);
        });

        $this->app->alias(AdminStudio::class, 'admin.studio');
    }

    public function boot(): void
    {
        $config = $this->app->has('config') ? $this->app->make('config') : null;
        $enabled = $config ? (bool) $config->get('admin.enabled', true) : true;
        if (!$enabled) {
            return;
        }

        $prefix = $config ? (string) $config->get('admin.prefix', 'admin') : 'admin';

        if ($this->app->has(RouterInterface::class)) {
            /** @var RouterInterface $router */
            $router = $this->app->make(RouterInterface::class);

            $router->get("/{$prefix}", [AdminController::class, 'dashboard'])->name('admin.dashboard');
            $router->get("/{$prefix}/api/overview", [AdminController::class, 'overview'])->name('admin.api.overview');
        }
    }
}
