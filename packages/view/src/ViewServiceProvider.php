<?php

declare(strict_types=1);

namespace CnA\View;

use CnA\Contracts\View\HtmlEscaperInterface;
use CnA\Contracts\View\ViewEngineInterface;
use CnA\Core\ServiceProvider;
use CnA\View\Components\ComponentRegistry;
use CnA\View\Engine\ViewEngine;
use CnA\View\Renderer\ComponentRenderer;
use CnA\View\Renderer\HtmlEscaper;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once __DIR__ . '/Helpers/view_helpers.php';

        $this->app->singleton(HtmlEscaperInterface::class, fn () => new HtmlEscaper());

        $this->app->singleton(ComponentRegistry::class, function () {
            return new ComponentRegistry();
        });

        $this->app->singleton(ViewEngineInterface::class, function () {
            $paths = (array) $this->app->make('config')->get('view.paths', [
                $this->app->basePath('resources/views'),
                $this->app->basePath('skeleton/resources/views'),
            ]);

            $engine = new ViewEngine($this->app, $paths);

            /** @var ComponentRegistry $registry */
            $registry = $this->app->make(ComponentRegistry::class);
            $componentRenderer = new ComponentRenderer($registry, $engine);

            $engine->setComponentRenderer($componentRenderer);

            return $engine;
        });

        $this->app->alias(ViewEngineInterface::class, ViewEngine::class);
        $this->app->alias(ViewEngineInterface::class, 'view.engine');
    }

    public function boot(): void
    {
    }
}

