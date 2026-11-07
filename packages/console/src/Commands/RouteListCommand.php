<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Contracts\Routing\RouterInterface;

class RouteListCommand extends Command
{
    protected string $name = 'route:list';
    protected string $description = 'List all registered HTTP routes';

    public function handle(): int
    {
        /** @var RouterInterface $router */
        $router = $this->app->get(RouterInterface::class);

        if (!method_exists($router, 'getRoutes')) {
            $this->error('Router does not support listing routes.');
            return Command::FAILURE;
        }

        $routes = $router->getRoutes();
        if (empty($routes)) {
            $this->comment('No routes registered.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($routes as $route) {
            $methods = implode('|', $route->getMethods());
            $uri = $route->getUri();
            $name = $route->getName() ?? '';
            $action = $route->getAction();

            $actionStr = match (true) {
                is_array($action) => is_object($action[0]) ? get_class($action[0]) . '@' . $action[1] : $action[0] . '@' . $action[1],
                is_string($action) => $action,
                $action instanceof \Closure => 'Closure',
                default => 'Unknown',
            };

            $middleware = implode(', ', $route->getMiddleware());

            $rows[] = [$methods, $uri, $name, $actionStr, $middleware];
        }

        $this->table(['Method', 'URI', 'Name', 'Action', 'Middleware'], $rows);
        return Command::SUCCESS;
    }
}

