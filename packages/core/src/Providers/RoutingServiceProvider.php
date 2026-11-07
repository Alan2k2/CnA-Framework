<?php

declare(strict_types=1);

namespace CnA\Core\Providers;

use CnA\Contracts\Routing\RouterInterface;
use CnA\Core\ServiceProvider;
use CnA\Http\Kernel;
use CnA\Middleware\CorsMiddleware;
use CnA\Middleware\CsrfProtectionMiddleware;
use CnA\Middleware\RateLimiterMiddleware;
use CnA\Middleware\SecurityHeadersMiddleware;
use CnA\Routing\Router;

class RoutingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RouterInterface::class, function () {
            $router = new Router($this->app);
            $router->aliasMiddleware('csrf', CsrfProtectionMiddleware::class);
            $router->aliasMiddleware('security', SecurityHeadersMiddleware::class);
            $router->aliasMiddleware('throttle', RateLimiterMiddleware::class);
            return $router;
        });

        $this->app->alias(RouterInterface::class, Router::class);
        $this->app->alias(RouterInterface::class, 'router');

        $this->app->singleton(Kernel::class, function () {
            /** @var RouterInterface $router */
            $router = $this->app->get(RouterInterface::class);
            $kernel = new Kernel($this->app, $router);
            $kernel->setMiddleware([
                SecurityHeadersMiddleware::class,
            ]);
            return $kernel;
        });
    }

    public function boot(): void
    {
        $routesPath = $this->app->basePath('routes');
        if (is_dir($routesPath)) {
            /** @var RouterInterface $router */
            $router = $this->app->get(RouterInterface::class);

            if (file_exists($routesPath . '/api.php')) {
                require $routesPath . '/api.php';
            }
            if (file_exists($routesPath . '/web.php')) {
                require $routesPath . '/web.php';
            }
        }
    }
}

