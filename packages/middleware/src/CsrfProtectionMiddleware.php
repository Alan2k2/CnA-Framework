<?php

declare(strict_types=1);

namespace CnA\Middleware;

use CnA\Contracts\Exceptions\SecurityException;
use CnA\Contracts\Http\MiddlewareInterface;
use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;

class CsrfProtectionMiddleware implements MiddlewareInterface
{
    /** @var array<string> */
    protected array $except = [
        'api/*',
    ];

    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        if ($this->isSafeMethod($request) || $this->inExceptArray($request)) {
            return $next($request);
        }

        $sessionToken = $request->cookie('XSRF-TOKEN') ?? $request->getAttribute('csrf_token');
        $submittedToken = $request->header('X-CSRF-TOKEN')
            ?? $request->header('X-XSRF-TOKEN')
            ?? $request->input('_token');

        if (!$sessionToken || !$submittedToken || !hash_equals((string) $sessionToken, (string) $submittedToken)) {
            throw new SecurityException('CSRF token mismatch.', 419);
        }

        return $next($request);
    }

    protected function isSafeMethod(RequestInterface $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    protected function inExceptArray(RequestInterface $request): bool
    {
        $path = trim($request->path(), '/');

        foreach ($this->except as $except) {
            $except = trim($except, '/');
            if ($except === $path) {
                return true;
            }
            if (str_ends_with($except, '*')) {
                $prefix = rtrim($except, '*');
                if (str_starts_with($path, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }
}

