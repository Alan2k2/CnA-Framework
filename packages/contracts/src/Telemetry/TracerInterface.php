<?php

declare(strict_types=1);

namespace CnA\Contracts\Telemetry;

interface TracerInterface
{
    /**
     * Start a new telemetry trace span.
     */
    public function startSpan(string $name, array $attributes = []): string;

    /**
     * End a span and record latency.
     */
    public function endSpan(string $spanId, array $attributes = []): void;

    /**
     * Get the active trace ID.
     */
    public function getTraceId(): string;

    /**
     * Get the active correlation ID.
     */
    public function getCorrelationId(): string;

    /**
     * Set a custom correlation ID (e.g. from incoming X-Correlation-ID header).
     */
    public function setCorrelationId(string $id): void;
}

