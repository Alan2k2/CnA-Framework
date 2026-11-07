<?php

declare(strict_types=1);

namespace CnA\Ai\Safety;

use CnA\Contracts\Exceptions\SecurityException;

class PromptGuard
{
    protected static array $injectionPatterns = [
        '/ignore\s+(all\s+)?previous\s+instructions/i',
        '/disregard\s+(all\s+)?previous\s+prompts/i',
        '/you\s+are\s+now\s+in\s+developer\s+mode/i',
        '/system\s+override/i',
        '/jailbreak/i',
    ];

    public static function checkPromptInjection(string $prompt): bool
    {
        foreach (static::$injectionPatterns as $pattern) {
            if (preg_match($pattern, $prompt)) {
                return true;
            }
        }
        return false;
    }

    public static function sanitizePrompt(string $prompt): string
    {
        if (static::checkPromptInjection($prompt)) {
            throw new SecurityException('Prompt injection detected in input string.');
        }

        return static::redactSecrets($prompt);
    }

    public static function redactSecrets(string $text): string
    {
        // Redact bearer tokens
        $text = preg_replace('/bearer\s+[a-zA-Z0-9_\-\.]{16,}/i', 'Bearer [REDACTED]', $text);
        // Redact API keys / secrets
        $text = preg_replace('/(api[_-]?key|secret|password|access_token)\s*[:=]\s*["\']?[a-zA-Z0-9_\-]{8,}["\']?/i', '$1: [REDACTED]', (string) $text);
        // Redact email addresses if PII protection enabled
        return (string) $text;
    }
}

