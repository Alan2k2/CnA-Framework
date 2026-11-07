<?php

declare(strict_types=1);

namespace CnA\Testing;

use CnA\Contracts\Core\ApplicationInterface;
use CnA\Http\Kernel;
use CnA\Http\Request;

abstract class TestCase
{
    protected ?ApplicationInterface $app = null;
    protected array $defaultHeaders = [];

    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    public function createApplication(): ApplicationInterface
    {
        $root = dirname(__DIR__, 3);
        if (file_exists($root . '/skeleton/bootstrap/app.php')) {
            $app = require $root . '/skeleton/bootstrap/app.php';
            $app->bootstrap();
            return $app;
        }

        $app = new \CnA\Core\Application($root);
        $app->bootstrap();
        return $app;
    }

    public function withHeaders(array $headers): static
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    public function get(string $uri, array $headers = []): TestResponse
    {
        return $this->call('GET', $uri, [], [], [], array_merge($this->defaultHeaders, $headers));
    }

    public function post(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('POST', $uri, $data, [], [], array_merge($this->defaultHeaders, $headers));
    }

    public function put(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('PUT', $uri, $data, [], [], array_merge($this->defaultHeaders, $headers));
    }

    public function delete(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('DELETE', $uri, $data, [], [], array_merge($this->defaultHeaders, $headers));
    }

    public function call(
        string $method,
        string $uri,
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $headers = []
    ): TestResponse {
        if ($this->app === null) {
            $this->app = $this->createApplication();
        }

        $rawBody = null;
        $post = [];

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $headers['content-type'] = 'application/json';
            $rawBody = json_encode($parameters);
            $post = $parameters;
        }

        $request = new Request(
            $method,
            $uri,
            $headers,
            $rawBody,
            strtoupper($method) === 'GET' ? $parameters : [],
            $post,
            $cookies,
            $files
        );

        /** @var Kernel $kernel */
        $kernel = $this->app->get(Kernel::class);
        $response = $kernel->handle($request);

        return new TestResponse($response);
    }

    // Common assertions
    public function assertTrue(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            throw new \RuntimeException($message ?: "Failed asserting that condition is true.");
        }
    }

    public function assertFalse(bool $condition, string $message = ''): void
    {
        if ($condition) {
            throw new \RuntimeException($message ?: "Failed asserting that condition is false.");
        }
    }

    public function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $exp = var_export($expected, true);
            $act = var_export($actual, true);
            throw new \RuntimeException($message ?: "Failed asserting that {$act} equals expected {$exp}.");
        }
    }

    public function assertNull(mixed $actual, string $message = ''): void
    {
        if ($actual !== null) {
            $act = var_export($actual, true);
            throw new \RuntimeException($message ?: "Failed asserting that {$act} is null.");
        }
    }

    public function assertNotNull(mixed $actual, string $message = ''): void
    {
        if ($actual === null) {
            throw new \RuntimeException($message ?: "Failed asserting that value is not null.");
        }
    }

    public function assertCount(int $expectedCount, countable|array $haystack, string $message = ''): void
    {
        $actual = count($haystack);
        if ($expectedCount !== $actual) {
            throw new \RuntimeException($message ?: "Failed asserting that count {$actual} matches expected {$expectedCount}.");
        }
    }
}
