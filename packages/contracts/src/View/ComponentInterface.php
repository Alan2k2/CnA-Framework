<?php

declare(strict_types=1);

namespace CnA\Contracts\View;

interface ComponentInterface
{
    /**
     * Render the component to an HTML string.
     *
     * @return string
     */
    public function render(): string;
}
