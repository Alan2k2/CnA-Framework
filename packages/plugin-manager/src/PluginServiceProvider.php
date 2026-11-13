<?php

declare(strict_types=1);

namespace CnA\Plugins;

use CnA\Contracts\Plugins\PluginManagerInterface;
use CnA\Core\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManagerInterface::class, function ($app) {
            $path = (string) ($app->make('config')->get('plugins.path') ?? (defined('CNA_ROOT') ? CNA_ROOT . '/plugins' : getcwd() . '/plugins'));
            return new PluginManager($app, $path);
        });

        $this->app->alias(PluginManagerInterface::class, 'plugins');
    }

    public function boot(): void
    {
        $manager = $this->app->make(PluginManagerInterface::class);
        $manager->discover();
        $manager->boot();
    }
}
