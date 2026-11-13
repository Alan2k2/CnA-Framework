<?php

declare(strict_types=1);

namespace CnA\View\Components;

use CnA\Contracts\View\ComponentInterface;
use CnA\View\Renderer\HtmlEscaper;

abstract class BaseComponent implements ComponentInterface
{
    /** @var array<string, mixed> */
    protected array $props = [];

    /**
     * @param array<string, mixed> $props
     */
    public function __construct(array $props = [])
    {
        $this->props = array_merge($this->defaultProps(), $props);
    }

    /**
     * Default prop values.
     *
     * @return array<string, mixed>
     */
    protected function defaultProps(): array
    {
        return [];
    }

    /**
     * Get a prop value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->props[$key] ?? $default;
    }

    /**
     * Escape string safely.
     */
    protected function e(mixed $value): string
    {
        return HtmlEscaper::escape($value);
    }

    /**
     * Build an HTML attribute string safely.
     *
     * @param array<string, mixed> $attributes
     */
    protected function buildAttributes(array $attributes): string
    {
        $html = [];
        foreach ($attributes as $key => $val) {
            if ($val === true) {
                $html[] = HtmlEscaper::escape($key);
            } elseif ($val !== false && $val !== null) {
                $html[] = sprintf('%s="%s"', HtmlEscaper::escape($key), HtmlEscaper::escapeAttribute($val));
            }
        }
        return !empty($html) ? ' ' . implode(' ', $html) : '';
    }

    /**
     * Render the component HTML.
     */
    abstract public function render(): string;
}
