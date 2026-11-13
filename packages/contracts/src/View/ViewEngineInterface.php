<?php

declare(strict_types=1);

namespace CnA\Contracts\View;

interface ViewEngineInterface
{
    /**
     * Render a given view template with contextual data.
     *
     * @param string $view
     * @param array<string, mixed> $data
     * @return string
     */
    public function render(string $view, array $data = []): string;

    /**
     * Determine if a view exists.
     *
     * @param string $view
     * @return bool
     */
    public function exists(string $view): bool;

    /**
     * Share data across all views.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function share(string $key, mixed $value): void;

    /**
     * Get shared view data.
     *
     * @param string|null $key
     * @return mixed
     */
    public function getShared(?string $key = null): mixed;
}

