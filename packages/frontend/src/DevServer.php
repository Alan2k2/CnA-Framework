<?php

declare(strict_types=1);

namespace CnA\Frontend;

use CnA\Contracts\Frontend\DevServerResolverInterface;
use InvalidArgumentException;

class DevServer implements DevServerResolverInterface
{
    protected ?bool $isRunningCache = null;

    /**
     * @param string $url Base URL of the Vite dev server (e.g. http://127.0.0.1:5173)
     * @param array<int, string> $allowedOrigins
     * @param float $timeout Timeout in seconds for connection check
     */
    public function __construct(
        protected string $url = 'http://127.0.0.1:5173',
        protected array $allowedOrigins = ['http://127.0.0.1:5173', 'http://localhost:5173', 'http://localhost:8000', 'http://127.0.0.1:8000'],
        protected float $timeout = 0.05
    ) {
        $this->url = rtrim($url, '/');
        $this->validateDevServerUrl($this->url);
    }

    /**
     * Validate that the dev server URL binds strictly to loopback to prevent external exposure.
     */
    protected function validateDevServerUrl(string $url): void
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException("Invalid Vite dev server URL [{$url}].");
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException("Unsupported scheme [{$scheme}] for Vite dev server. Must be http or https.");
        }

        $host = strtolower($parts['host']);
        $allowedHosts = ['127.0.0.1', 'localhost', '::1', '[::1]'];
        if (!in_array($host, $allowedHosts, true) && !str_ends_with($host, '.localhost')) {
            throw new InvalidArgumentException("Dev server exposure blocked: host [{$host}] must bind to loopback (127.0.0.1 or localhost).");
        }
    }

    public function isTrustedOrigin(string $url): bool
    {
        $origin = rtrim($url, '/');
        return in_array($origin, $this->allowedOrigins, true);
    }

    public function isRunning(): bool
    {
        if ($this->isRunningCache !== null) {
            return $this->isRunningCache;
        }

        $parts = parse_url($this->url);
        $host = $parts['host'] ?? '127.0.0.1';
        $port = isset($parts['port']) ? (int) $parts['port'] : ($parts['scheme'] === 'https' ? 443 : 5173);

        $fp = @fsockopen($host, $port, $errno, $errstr, $this->timeout);
        if ($fp) {
            fclose($fp);
            $this->isRunningCache = true;
            return true;
        }

        $this->isRunningCache = false;
        return false;
    }

    public function setRunning(bool $status): self
    {
        $this->isRunningCache = $status;
        return $this;
    }

    public function getUrl(?string $path = null): string
    {
        if ($path === null || $path === '') {
            return $this->url;
        }
        return $this->url . '/' . ltrim($path, '/');
    }

    public function clientScriptTag(?string $nonce = null): string
    {
        $nonceAttr = $nonce ? ' nonce="' . htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') . '"' : '';
        $clientUrl = htmlspecialchars($this->getUrl('@vite/client'), ENT_QUOTES, 'UTF-8');
        return sprintf('<script type="module" src="%s"%s></script>', $clientUrl, $nonceAttr);
    }
}

