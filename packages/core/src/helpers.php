<?php

declare(strict_types=1);

use CnA\Config\Config;
use CnA\Config\Env;
use CnA\Container\Container;
use CnA\Contracts\Core\ApplicationInterface;
use CnA\Contracts\Exceptions\NotFoundException;
use CnA\Contracts\Http\ResponseInterface;
use CnA\Contracts\Routing\RouterInterface;
use CnA\Http\JsonResponse;
use CnA\Http\Response;

if (!function_exists('app')) {
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        $container = Container::getInstance();
        if ($abstract === null) {
            return $container;
        }
        return $container->make($abstract, $parameters);
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        /** @var Config $config */
        $config = app('config');
        if ($key === null) {
            return $config;
        }
        return $config->get($key, $default);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('response')) {
    function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }
}

if (!function_exists('view')) {
    function view(string $viewName, array $data = [], int $status = 200, array $headers = []): Response
    {
        $html = \CnA\Core\View::make($viewName, $data);
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        return new Response($html, $status, $headers);
    }
}

if (!function_exists('json')) {
    function json(mixed $data = [], int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}

if (!function_exists('route')) {
    function route(string $name, array $parameters = []): string
    {
        /** @var RouterInterface $router */
        $router = app(RouterInterface::class);
        return $router->route($name, $parameters);
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = ''): never
    {
        if ($code === 404) {
            throw new NotFoundException($message ?: 'Resource not found.');
        }
        throw new \RuntimeException($message ?: "HTTP error {$code}", $code);
    }
}

if (!function_exists('bcrypt')) {
    function bcrypt(string $value, array $options = []): string
    {
        $cost = $options['cost'] ?? 12;
        return password_hash($value, PASSWORD_BCRYPT, ['cost' => $cost]);
    }
}
