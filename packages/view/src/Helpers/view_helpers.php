<?php

declare(strict_types=1);

use CnA\Contracts\View\ViewEngineInterface;
use CnA\View\Renderer\HtmlEscaper;

if (!function_exists('component')) {
    /**
     * Render an accessible server-rendered CnA UI component.
     *
     * @param string $name Component alias (e.g., 'ui.button', 'ui.alert') or template path
     * @param array<string, mixed> $props
     * @return string
     */
    function component(string $name, array $props = []): string
    {
        if (function_exists('app') && app()->has(ViewEngineInterface::class)) {
            /** @var \CnA\View\Engine\ViewEngine $engine */
            $engine = app(ViewEngineInterface::class);
            return $engine->component($name, $props);
        }

        $registry = new \CnA\View\Components\ComponentRegistry();
        $renderer = new \CnA\View\Renderer\ComponentRenderer($registry);
        return $renderer->render($name, $props);
    }
}

if (!function_exists('layout')) {
    /**
     * Define the outer layout template for the current view.
     *
     * @param string $name (e.g. 'layouts.app')
     * @return void
     */
    function layout(string $name): void
    {
        if (function_exists('app') && app()->has(ViewEngineInterface::class)) {
            /** @var \CnA\View\Engine\ViewEngine $engine */
            $engine = app(ViewEngineInterface::class);
            $engine->layout($name);
        } else {
            \CnA\Core\View::layout($name);
        }
    }
}

if (!function_exists('section')) {
    /**
     * Start capturing content for a named layout section.
     *
     * @param string $name
     * @return void
     */
    function section(string $name): void
    {
        if (function_exists('app') && app()->has(ViewEngineInterface::class)) {
            /** @var \CnA\View\Engine\ViewEngine $engine */
            $engine = app(ViewEngineInterface::class);
            $engine->startSection($name);
        } else {
            \CnA\Core\View::startSection($name);
        }
    }
}

if (!function_exists('endsection')) {
    /**
     * Stop capturing content for the current layout section.
     *
     * @return void
     */
    function endsection(): void
    {
        if (function_exists('app') && app()->has(ViewEngineInterface::class)) {
            /** @var \CnA\View\Engine\ViewEngine $engine */
            $engine = app(ViewEngineInterface::class);
            $engine->endSection();
        } else {
            \CnA\Core\View::endSection();
        }
    }
}

if (!function_exists('yield_section')) {
    /**
     * Output captured section content or default fallback.
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    function yield_section(string $name, string $default = ''): string
    {
        if (function_exists('app') && app()->has(ViewEngineInterface::class)) {
            /** @var \CnA\View\Engine\ViewEngine $engine */
            $engine = app(ViewEngineInterface::class);
            return $engine->yieldSection($name, $default);
        }
        return \CnA\Core\View::yield($name, $default);
    }
}

if (!function_exists('e')) {
    /**
     * Safely escape HTML characters.
     *
     * @param mixed $value
     * @return string
     */
    function e(mixed $value): string
    {
        return HtmlEscaper::escape($value);
    }
}
