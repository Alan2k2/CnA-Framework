<?php

declare(strict_types=1);

namespace CnA\Contracts\View;

interface HtmlEscaperInterface
{
    /**
     * Escape HTML special characters for safe output.
     *
     * @param mixed $value
     * @param bool $doubleEncode
     * @return string
     */
    public static function escape(mixed $value, bool $doubleEncode = true): string;

    /**
     * Escape an HTML attribute value.
     *
     * @param mixed $value
     * @return string
     */
    public static function escapeAttribute(mixed $value): string;
}

