<?php

declare(strict_types=1);

namespace CnA\Devtools;

use CnA\Contracts\Http\MiddlewareInterface;
use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;
use CnA\Contracts\Telemetry\TracerInterface;

class CorrelationMiddleware implements MiddlewareInterface
{
    public function __construct(protected TracerInterface $tracer)
    {
    }

    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $correlationId = $request->header('X-Correlation-ID') ?: $request->header('x-correlation-id');
        if (!empty($correlationId)) {
            $this->tracer->setCorrelationId((string) $correlationId);
        }

        $method = method_exists($request, 'getMethod') ? $request->getMethod() : (method_exists($request, 'method') ? $request->method() : 'GET');
        $uri = method_exists($request, 'getUri') ? (string) $request->getUri() : (method_exists($request, 'uri') ? $request->uri() : '/');

        $spanId = $this->tracer->startSpan('http.request', [
            'method' => $method,
            'uri' => $uri,
        ]);

        /** @var ResponseInterface $response */
        $response = $next($request);

        $this->tracer->endSpan($spanId, [
            'status_code' => $response->getStatusCode(),
        ]);

        return $response
            ->withHeader('X-Correlation-ID', $this->tracer->getCorrelationId())
            ->withHeader('X-Trace-ID', $this->tracer->getTraceId());
    }
}
