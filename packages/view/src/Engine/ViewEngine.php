<?php

declare(strict_types=1);

namespace CnA\View\Engine;

use CnA\Contracts\Core\ApplicationInterface;
use CnA\Contracts\View\ViewEngineInterface;
use CnA\View\Components\ComponentRegistry;
use CnA\View\Renderer\ComponentRenderer;
use CnA\View\Renderer\HtmlEscaper;

class ViewEngine implements ViewEngineInterface
{
    /** @var array<int, string> */
    protected array $paths = [];

    /** @var array<string, mixed> */
    protected array $shared = [];

    /** @var array<string, string> */
    protected array $sections = [];

    protected ?string $currentSection = null;
    protected ?string $currentLayout = null;

    protected ?ComponentRenderer $componentRenderer = null;

    public function __construct(
        protected ?ApplicationInterface $app = null,
        array $paths = []
    ) {
        if (!empty($paths)) {
            $this->paths = $paths;
        } elseif ($app !== null) {
            $this->paths = [
                $app->basePath('resources/views'),
                $app->basePath('skeleton/resources/views'),
            ];
        }
    }

    public function addPath(string $path): self
    {
        if (!in_array($path, $this->paths, true)) {
            $this->paths[] = rtrim($path, '/\\');
        }
        return $this;
    }

    public function setComponentRenderer(ComponentRenderer $renderer): self
    {
        $this->componentRenderer = $renderer;
        return $this;
    }

    public function getComponentRenderer(): ComponentRenderer
    {
        if ($this->componentRenderer === null) {
            $registry = new ComponentRegistry();
            $this->componentRenderer = new ComponentRenderer($registry, $this);
        }
        return $this->componentRenderer;
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function getShared(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->shared;
        }
        return $this->shared[$key] ?? null;
    }

    public function exists(string $view): bool
    {
        return $this->findViewPath($view) !== null;
    }

    public function render(string $view, array $data = []): string
    {
        $viewPath = $this->findViewPath($view);
        if ($viewPath === null) {
            $searched = [];
            $rel = str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';
            foreach ($this->paths as $p) {
                $searched[] = $p . DIRECTORY_SEPARATOR . $rel;
            }
            throw new ViewNotFoundException($view, $searched);
        }

        $mergedData = array_merge($this->shared, $data);

        // Render template in isolated closure
        $content = $this->renderFile($viewPath, $mergedData);

        // If a layout was declared during render, wrap it
        if ($this->currentLayout !== null) {
            $layout = $this->currentLayout;
            $this->currentLayout = null;

            // Store default content section if not already set
            if (!isset($this->sections['content'])) {
                $this->sections['content'] = $content;
            }

            return $this->render($layout, $mergedData);
        }

        return $content;
    }

    protected function renderFile(string $filePath, array $data): string
    {
        $runner = function () use ($filePath, $data): string {
            extract($data, EXTR_SKIP);
            ob_start();
            try {
                include $filePath;
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            return ob_get_clean() ?: '';
        };

        return $runner();
    }

    public function layout(string $layout): void
    {
        $this->currentLayout = $layout;
    }

    public function startSection(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection !== null) {
            $this->sections[$this->currentSection] = ob_get_clean() ?: '';
            $this->currentSection = null;
        }
    }

    public function yieldSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function component(string $name, array $props = []): string
    {
        return $this->getComponentRenderer()->render($name, $props);
    }

    public function escape(mixed $value): string
    {
        return HtmlEscaper::escape($value);
    }

    protected function findViewPath(string $view): ?string
    {
        $normalized = str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';

        foreach ($this->paths as $base) {
            $candidate = $base . DIRECTORY_SEPARATOR . $normalized;
            if (file_exists($candidate) && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
