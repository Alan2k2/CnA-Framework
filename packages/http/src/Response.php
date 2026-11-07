<?php

declare(strict_types=1);

namespace CnA\Http;

use CnA\Contracts\Http\ResponseInterface;

class Response implements ResponseInterface
{
    protected int $statusCode;
    protected string $reasonPhrase;
    /** @var array<string, array<string>> */
    protected array $headers = [];
    protected string $body;

    protected static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    public function __construct(string $body = '', int $status = 200, array $headers = [])
    {
        $this->statusCode = $status;
        $this->reasonPhrase = self::$statusTexts[$status] ?? 'Unknown';
        $this->body = $body;

        foreach ($headers as $name => $values) {
            $this->setHeader($name, (array) $values);
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase !== '' ? $reasonPhrase : (self::$statusTexts[$code] ?? 'Unknown');
        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, string|array $value): static
    {
        $clone = clone $this;
        $clone->setHeader($name, (array) $value);
        return $clone;
    }

    public function withAddedHeader(string $name, string|array $value): static
    {
        $clone = clone $this;
        $key = strtolower($name);
        $values = (array) $value;
        if (isset($clone->headers[$key])) {
            $clone->headers[$key] = array_merge($clone->headers[$key], $values);
        } else {
            $clone->setHeader($name, $values);
        }
        return $clone;
    }

    public function withoutHeader(string $name): static
    {
        $clone = clone $this;
        unset($clone->headers[strtolower($name)]);
        return $clone;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function withBody(string $body): static
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $values) {
                foreach ($values as $value) {
                    header("{$name}: {$value}", false);
                }
            }
        }

        echo $this->body;
    }

    protected function setHeader(string $name, array $values): void
    {
        $this->headers[strtolower($name)] = array_values($values);
    }
}

