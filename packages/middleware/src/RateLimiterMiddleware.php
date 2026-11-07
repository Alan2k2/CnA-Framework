<?php

declare(strict_types=1);

namespace CnA\Middleware;

use CnA\Contracts\Cache\CacheInterface;
use CnA\Contracts\Http\MiddlewareInterface;
use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;
use CnA\Http\JsonResponse;

class RateLimiterMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected ?CacheInterface $cache = null,
        protected int $maxAttempts = 60,
        protected int $decaySeconds = 60
    ) {
    }

    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        if ($this->cache === null) {
            return $next($request);
        }

        $key = 'rate_limit:' . sha1($request->ip() . '|' . $request->path());
        $current = (int) $this->cache->get($key, 0);

        if ($current >= $this->maxAttempts) {
            return new JsonResponse([
                'message' => 'Too Many Requests',
                'retry_after' => $this->decaySeconds,
            ], 429, [
                'Retry-After' => (string) $this->decaySeconds,
                'X-RateLimit-Limit' => (string) $this->maxAttempts,
                'X-RateLimit-Remaining' => '0',
            ]);
        }

        $this->cache->set($key, $current + 1, $this->decaySeconds);

        /** @var ResponseInterface $response */
        $response = $next($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) ($this->maxAttempts - ($current + 1)));
    }
}

