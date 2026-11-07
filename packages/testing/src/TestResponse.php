<?php

declare(strict_types=1);

namespace CnA\Testing;

use CnA\Contracts\Http\ResponseInterface;

class TestResponse
{
    public function __construct(public readonly ResponseInterface $response)
    {
    }

    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    public function body(): string
    {
        return $this->response->getBody();
    }

    public function json(?string $key = null): mixed
    {
        $data = json_decode($this->body(), true);
        if ($key === null) {
            return $data;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($data) && array_key_exists($segment, $data)) {
                $data = $data[$segment];
            } else {
                return null;
            }
        }

        return $data;
    }

    public function assertStatus(int $expected): static
    {
        $actual = $this->status();
        if ($actual !== $expected) {
            throw new \RuntimeException("Expected HTTP status {$expected}, but received {$actual}. Response: {$this->body()}");
        }
        return $this;
    }

    public function assertOk(): static
    {
        return $this->assertStatus(200);
    }

    public function assertCreated(): static
    {
        return $this->assertStatus(201);
    }

    public function assertNotFound(): static
    {
        return $this->assertStatus(404);
    }

    public function assertForbidden(): static
    {
        return $this->assertStatus(403);
    }

    public function assertUnauthorized(): static
    {
        return $this->assertStatus(401);
    }

    public function assertUnprocessable(): static
    {
        return $this->assertStatus(422);
    }

    public function assertJson(array $expected): static
    {
        $actual = $this->json();
        if (!is_array($actual)) {
            throw new \RuntimeException("Response is not valid JSON: {$this->body()}");
        }

        foreach ($expected as $k => $v) {
            if (!array_key_exists($k, $actual) || $actual[$k] !== $v) {
                $actualStr = json_encode($actual);
                $expectedStr = json_encode($expected);
                throw new \RuntimeException("JSON subset check failed. Expected: {$expectedStr}, Actual: {$actualStr}");
            }
        }

        return $this;
    }

    public function assertSee(string $value): static
    {
        if (!str_contains($this->body(), $value)) {
            throw new \RuntimeException("Response body does not contain expected substring [{$value}].");
        }
        return $this;
    }
}

