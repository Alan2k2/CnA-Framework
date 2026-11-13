<?php

declare(strict_types=1);

namespace CnA\Admin;

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
}
