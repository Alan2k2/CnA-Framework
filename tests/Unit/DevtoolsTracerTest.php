<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Devtools\CorrelationMiddleware;
use CnA\Devtools\Tracer;
use CnA\Http\Request;
use CnA\Http\Response;
use CnA\Testing\TestCase;

class DevtoolsTracerTest extends TestCase
{
    public function testTracerSpanRecording(): void
    {
        $tracer = new Tracer();

        $this->assertNotNull($tracer->getTraceId());
        $this->assertNotNull($tracer->getCorrelationId());

        $tracer->setCorrelationId('req_corr_abc123');
        $this->assertEquals('req_corr_abc123', $tracer->getCorrelationId());

        $spanId = $tracer->startSpan('db.query', ['sql' => 'SELECT 1']);
        $this->assertNotNull($spanId);

        usleep(2000); // 2ms
        $tracer->endSpan($spanId, ['rows' => 1]);

        $spans = $tracer->getSpans();
        $this->assertTrue(isset($spans[$spanId]));
        $this->assertEquals('db.query', $spans[$spanId]['name']);
        $this->assertTrue(isset($spans[$spanId]['duration_ms']));
        $this->assertTrue($spans[$spanId]['duration_ms'] >= 0.5);
        $this->assertEquals(1, $spans[$spanId]['attributes']['rows']);
    }

    public function testCorrelationMiddlewareInjectsHeaders(): void
    {
        $tracer = new Tracer();
        $middleware = new CorrelationMiddleware($tracer);

        $request = new Request('GET', '/test', ['X-Correlation-ID' => 'custom-corr-777']);

        $response = $middleware->process($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals('custom-corr-777', $tracer->getCorrelationId());
        $this->assertTrue($response->hasHeader('X-Correlation-ID'));
        $this->assertTrue($response->hasHeader('X-Trace-ID'));
        $this->assertEquals('custom-corr-777', $response->getHeaderLine('X-Correlation-ID'));
    }
}

