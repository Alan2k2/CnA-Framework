<?php

declare(strict_types=1);

namespace CnA\Monitoring;

class QueryNormalizer
{
    public static function fingerprint(string $sql): string
    {
        // Replace single-quoted strings
        $normalized = preg_replace("/'[^']*'/", '?', $sql);
        // Replace double-quoted strings
        $normalized = preg_replace('/"[^"]*"/', '?', (string) $normalized);
        // Replace numeric literals
        $normalized = preg_replace('/\b\d+\b/', '?', (string) $normalized);
        // Normalize whitespace
        $normalized = preg_replace('/\s+/', ' ', (string) $normalized);

        return trim(strtoupper((string) $normalized));
    }

    public static function redactBindings(array $bindings): array
    {
        $sensitiveKeys = ['password', 'secret', 'token', 'key', 'auth', 'card', 'cvv', 'ssn'];

        $redacted = [];
        foreach ($bindings as $key => $value) {
            $isSensitive = false;
            if (is_string($key)) {
                $lowerKey = strtolower($key);
                foreach ($sensitiveKeys as $pattern) {
                    if (str_contains($lowerKey, $pattern)) {
                        $isSensitive = true;
                        break;
                    }
                }
            }

            $redacted[$key] = $isSensitive ? '********' : $value;
        }

        return $redacted;
    }
}

