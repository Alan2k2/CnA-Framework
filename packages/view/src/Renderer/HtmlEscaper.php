<?php

declare(strict_types=1);

namespace CnA\View\Renderer;

use CnA\Contracts\View\HtmlEscaperInterface;

class HtmlEscaper implements HtmlEscaperInterface
{
    /**
     * Escape HTML special characters for safe output.
     */
    public static function escape(mixed $value, bool $doubleEncode = true): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }

    /**
     * Escape an HTML attribute value.
     */
    public static function escapeAttribute(mixed $value): string
    {
        return static::escape($value);
    }
}
