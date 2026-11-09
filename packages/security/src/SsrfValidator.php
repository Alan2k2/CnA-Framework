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

    /** @var array<string> */
    protected static array $blockedCidrs = [
        '0.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16', // Cloud metadata
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '192.88.99.0/24',
        '192.168.0.0/16',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        '240.0.0.0/4',
        '255.255.255.255/32',
    ];

    public function __construct(array $hostAllowlist = [])
    {
        $this->hostAllowlist = array_map('strtolower', $hostAllowlist);
    }

    public function validateUrl(string $url): string
    {
        $pinned = $this->validateAndPin($url);
        return $pinned['url'];
    }

    /**
     * Validates destination URL and returns DNS-pinned connection parameters.
     * Prevents DNS Rebinding (GHSA-c7hm-wqwp-f9v8) by fixing the exact validated IP.
     *
     * @return array{url: string, host: string, port: int, pinned_ip: string, scheme: string}
     */
    public function validateAndPin(string $url): array
    {
        // Reject null bytes or CRLF in URL
        if (preg_match('/[\0\r\n]/', $url)) {
            throw new SecurityException("Malformed URL containing forbidden control characters.");
        }

        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed['scheme'], $parsed['host'])) {
            throw new SecurityException("Malformed URL: {$url}");
        }

        $scheme = strtolower($parsed['scheme']);
        if (!in_array($scheme, $this->allowedSchemes, true)) {
            throw new SecurityException("Unsupported or dangerous URL scheme: {$scheme}");
        }

        // Canonicalize host (CVE-2026-69246 mitigation)
        $host = strtolower(rtrim($parsed['host'], '.'));
        $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);

        // Disallow embedded userinfo to prevent authority confusion
        if (isset($parsed['user']) || isset($parsed['pass'])) {
            throw new SecurityException("URLs with embedded userinfo credentials are not permitted.");
        }

        // Check allowlist if configured
        if (!empty($this->hostAllowlist) && !in_array($host, $this->hostAllowlist, true)) {
            throw new SecurityException("Host [{$host}] is not in the egress allowlist.");
        }

        // Determine if host is an explicit IP literal
        $isIpLiteral = filter_var($host, FILTER_VALIDATE_IP) !== false;

        $targetIps = [];
        if ($isIpLiteral) {
            $targetIps[] = $host;
        } else {
            // Validate domain name format
            if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                throw new SecurityException("Invalid hostname format: {$host}");
            }

            // Resolve DNS records
            $resolved = @gethostbynamel($host);
            if ($resolved === false || empty($resolved)) {
                throw new SecurityException("Could not resolve host: {$host}");
            }
            $targetIps = $resolved;
        }

        // Verify EVERY resolved IP address to prevent mixed dual-stack or multi-record bypasses
        $pinnedIp = null;
        foreach ($targetIps as $candidateIp) {
            if ($this->isPrivateOrReservedIp($candidateIp)) {
                throw new SecurityException("Outbound connection blocked to private, loopback, or metadata IP [{$candidateIp}]. Potential SSRF detected.");
            }
            if ($pinnedIp === null) {
                $pinnedIp = $candidateIp;
            }
        }

        return [
            'url' => $url,
            'host' => $host,
            'port' => $port,
            'pinned_ip' => $pinnedIp ?? $host,
            'scheme' => $scheme,
        ];
    }

    /**
     * Checks if IP is private, reserved, loopback, cloud metadata, or mapped IPv4 (GHSA-4qpf-57w4-m3wc).
     */
    public function isPrivateOrReservedIp(string $ip): bool
    {
        // Strip square brackets if IPv6 literal
        $cleanIp = trim($ip, '[]');

        // Check if IPv4-mapped or IPv4-compatible IPv6 (GHSA-4qpf-57w4-m3wc)
        $packed = @inet_pton($cleanIp);
        if ($packed !== false && strlen($packed) === 16) {
            // Check for IPv4-mapped IPv6 (::ffff:x.x.x.x) or IPv4-compatible (::x.x.x.x)
            $isMapped = substr($packed, 0, 10) === "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"
                && (substr($packed, 10, 2) === "\xff\xff" || substr($packed, 10, 2) === "\x00\x00");

            if ($isMapped) {
                $extractedIpv4 = inet_ntop(substr($packed, 12, 4));
                if ($extractedIpv4 !== false && $this->isPrivateOrReservedIp($extractedIpv4)) {
                    return true;
                }
            }

            // Loopback IPv6 (::1)
            if ($cleanIp === '::1' || $packed === "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01") {
                return true;
            }

            // Unspecified IPv6 (::)
            if ($packed === str_repeat("\x00", 16)) {
                return true;
            }

            // Unique local IPv6 (fc00::/7) or Link-local (fe80::/10)
            $firstByte = ord($packed[0]);
            if (($firstByte & 0xfe) === 0xfc || ($firstByte === 0xfe && (ord($packed[1]) & 0xc0) === 0x80)) {
                return true;
            }
        }

        // Standard filter_var check with RFC flags
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (filter_var($cleanIp, FILTER_VALIDATE_IP, $flags) === false) {
            return true;
        }

        // Check explicit IPv4 CIDRs including cloud metadata (169.254.169.254)
        if (filter_var($cleanIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($cleanIp);
            if ($ipLong === false) {
                return true;
            }

            foreach (static::$blockedCidrs as $cidr) {
                [$subnet, $mask] = explode('/', $cidr);
                $subnetLong = ip2long($subnet);
                $maskBits = ~((1 << (32 - (int) $mask)) - 1);

                if (($ipLong & $maskBits) === ($subnetLong & $maskBits)) {
                    return true;
                }
            }
        }

        return false;
    }
}
