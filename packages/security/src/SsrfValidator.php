<?php

declare(strict_types=1);

namespace CnA\Security;

use CnA\Contracts\Exceptions\SecurityException;

class SsrfValidator
{
    /** @var array<string> */
    protected array $allowedSchemes = ['http', 'https'];

    /** @var array<string> */
    protected array $hostAllowlist = [];

    public function __construct(array $hostAllowlist = [])
    {
        $this->hostAllowlist = $hostAllowlist;
    }

    public function validateUrl(string $url): string
    {
        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed['scheme'], $parsed['host'])) {
            throw new SecurityException("Malformed URL: {$url}");
        }

        $scheme = strtolower($parsed['scheme']);
        if (!in_array($scheme, $this->allowedSchemes, true)) {
            throw new SecurityException("Unsupported or dangerous URL scheme: {$scheme}");
        }

        $host = strtolower($parsed['host']);

        // Check allowlist if configured
        if (!empty($this->hostAllowlist) && !in_array($host, $this->hostAllowlist, true)) {
            throw new SecurityException("Host [{$host}] is not in the egress allowlist.");
        }

        // Resolve DNS and check against private / loopback IP ranges
        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new SecurityException("Could not resolve host: {$host}");
        }

        if ($this->isPrivateOrReservedIp($ip)) {
            throw new SecurityException("Outbound connection blocked to private or loopback IP [{$ip}]. Potential SSRF detected.");
        }

        return $url;
    }

    public function isPrivateOrReservedIp(string $ip): bool
    {
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        return filter_var($ip, FILTER_VALIDATE_IP, $flags) === false;
    }
}

