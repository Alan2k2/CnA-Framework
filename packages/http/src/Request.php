<?php

declare(strict_types=1);

namespace CnA\Http;

use CnA\Contracts\Http\RequestInterface;

class Request implements RequestInterface
{
    protected string $method;
    protected string $uri;
    protected string $path;
    protected array $query;
    protected array $post;
    protected array $headers;
    protected array $cookies;
    protected array $files;
    protected ?string $rawBody;
    protected mixed $parsedBody = null;
    protected string $correlationId;
    protected array $attributes = [];
    protected static array $trustedProxies = [];

    public function __construct(
        string $method = 'GET',
        string $uri = '/',
        array $headers = [],
        ?string $rawBody = null,
        array $query = [],
        array $post = [],
        array $cookies = [],
        array $files = []
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $parsedUrl = parse_url($uri);
        $this->path = $parsedUrl['path'] ?? '/';
        $this->headers = $this->normalizeHeaders($headers);
        $this->rawBody = $rawBody;
        $this->query = $query;
        $this->post = $post;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->correlationId = $this->header('x-correlation-id')
            ?? $this->header('x-request-id')
            ?? bin2hex(random_bytes(16));

        $this->parseBody();
    }

    public static function createFromGlobals(): static
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if (empty($headers)) {
            foreach ($_SERVER as $key => $value) {
                if (str_starts_with($key, 'HTTP_')) {
                    $headerName = str_replace('_', '-', substr($key, 5));
                    $headers[$headerName] = $value;
                }
            }
        }

        $rawBody = file_get_contents('php://input');

        return new static(
            $method,
            $uri,
            $headers,
            $rawBody === false ? null : $rawBody,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        );
    }

    public static function setTrustedProxies(array $proxies): void
    {
        static::$trustedProxies = $proxies;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $all = $this->all();
        if ($key === null) {
            return $all;
        }
        return $all[$key] ?? $default;
    }

    public function all(): array
    {
        if (is_array($this->parsedBody)) {
            return array_merge($this->query, $this->parsedBody);
        }
        return array_merge($this->query, $this->post);
    }

    public function header(string $key, ?string $default = null): ?string
    {
        $normalized = strtolower($key);
        return $this->headers[$normalized] ?? $default;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function cookie(string $key, ?string $default = null): ?string
    {
        return $this->cookies[$key] ?? $default;
    }

    public function cookies(): array
    {
        return $this->cookies;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('authorization');
        if ($header && preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function ip(): ?string
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Only trust forwarded headers if the remote IP is explicitly in the trusted proxies list
        if (!empty(static::$trustedProxies) && in_array($remoteAddr, static::$trustedProxies, true)) {
            $forwarded = $this->header('x-forwarded-for');
            if ($forwarded !== null) {
                $ips = array_map('trim', explode(',', $forwarded));
                return $ips[0] ?? $remoteAddr;
            }
        }

        return $remoteAddr;
    }

    public function isSecure(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';
        return (!empty($https) && $https !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    }

    public function isJson(): bool
    {
        $contentType = $this->header('content-type', '');
        return str_contains($contentType, '/json') || str_contains($contentType, '+json');
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('accept', '');
        return str_contains($accept, '/json') || str_contains($accept, '+json');
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function setCorrelationId(string $id): void
    {
        $this->correlationId = $id;
    }

    public function withAttribute(string $name, mixed $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    protected function parseBody(): void
    {
        if ($this->isJson() && !empty($this->rawBody)) {
            try {
                $decoded = json_decode($this->rawBody, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $this->parsedBody = $decoded;
                }
            } catch (\JsonException) {
                $this->parsedBody = [];
            }
        }
    }

    protected function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[strtolower((string) $key)] = is_array($value) ? implode(', ', $value) : (string) $value;
        }
        return $normalized;
    }
}

