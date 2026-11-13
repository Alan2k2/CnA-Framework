<?php

declare(strict_types=1);

namespace CnA\Devtools;

use CnA\Contracts\Telemetry\TracerInterface;

class Tracer implements TracerInterface
{
    protected string $traceId;
    protected string $correlationId;

    /** @var array<string, array{name: string, start: float, end?: float, duration_ms?: float, attributes: array}> */
    protected array $spans = [];

    public function __construct(?string $traceId = null, ?string $correlationId = null)
    {
        $this->traceId = $traceId ?? bin2hex(random_bytes(16));
        $this->correlationId = $correlationId ?? bin2hex(random_bytes(8));
    }

    public function startSpan(string $name, array $attributes = []): string
    {
        $spanId = bin2hex(random_bytes(8));
        $this->spans[$spanId] = [
            'name' => $name,
            'start' => microtime(true),
            'attributes' => $attributes,
        ];
        return $spanId;
    }

    public function endSpan(string $spanId, array $attributes = []): void
    {
        if (isset($this->spans[$spanId])) {
            $end = microtime(true);
            $this->spans[$spanId]['end'] = $end;
            $this->spans[$spanId]['duration_ms'] = round(($end - $this->spans[$spanId]['start']) * 1000, 2);
            $this->spans[$spanId]['attributes'] = array_merge($this->spans[$spanId]['attributes'], $attributes);
        }
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function setCorrelationId(string $id): void
    {
        $this->correlationId = $id;
    }

    /**
     * @return array<string, array{name: string, start: float, end?: float, duration_ms?: float, attributes: array}>
     */
    public function getSpans(): array
    {
        return $this->spans;
    }
}
