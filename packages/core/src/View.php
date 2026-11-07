<?php

declare(strict_types=1);

namespace CnA\Core;

use CnA\Contracts\Core\ApplicationInterface;
use CnA\Contracts\Exceptions\NotFoundException;
use CnA\Security\HtmlEscaper;

class View
{
    /** @var array<string, string> */
    protected static array $sections = [];
    protected static ?string $currentSection = null;
    protected static ?string $layout = null;

    public function __construct(protected ApplicationInterface $app)
    {
    }

    public static function make(string $view, array $data = []): string
    {
        $app = app();
        $instance = new static($app);
        return $instance->render($view, $data);
    }

    public function render(string $view, array $data = []): string
    {
        $path = $this->resolveViewPath($view);
        if ($path === null) {
            throw new NotFoundException("View [{$view}] not found.");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $path;
        $content = ob_get_clean();

        if (static::$layout !== null) {
            $layoutPath = $this->resolveViewPath(static::$layout);
            static::$layout = null;
            if ($layoutPath !== null) {
                ob_start();
                include $layoutPath;
                return ob_get_clean() ?: '';
            }
        }

        return $content ?: '';
    }

    public static function layout(string $layout): void
    {
        static::$layout = $layout;
    }

    public static function startSection(string $name): void
    {
        static::$currentSection = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        if (static::$currentSection !== null) {
            static::$sections[static::$currentSection] = ob_get_clean() ?: '';
            static::$currentSection = null;
        }
    }

    public static function yield(string $name, string $default = ''): string
    {
        return static::$sections[$name] ?? $default;
    }

    public static function escape(mixed $value): string
    {
        return HtmlEscaper::escape(is_string($value) ? $value : (string) $value);
    }

    protected function resolveViewPath(string $view): ?string
    {
        $viewFile = str_replace('.', '/', $view) . '.php';

        $candidates = [
            $this->app->basePath('resources/views/' . $viewFile),
            $this->app->basePath('skeleton/resources/views/' . $viewFile),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}

