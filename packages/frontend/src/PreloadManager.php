<?php

declare(strict_types=1);

namespace CnA\Frontend;

use CnA\Contracts\Frontend\PreloadManagerInterface;

class PreloadManager implements PreloadManagerInterface
{
    /** @var array<string, bool> */
    protected array $preloads = [];

    /** @var array<string, bool> */
    protected array $stylesheets = [];

    public function addPreload(string $url): void
    {
        $this->preloads[$url] = true;
    }

    public function getPreloads(): array
    {
        return array_keys($this->preloads);
    }

    public function addStylesheet(string $url): void
    {
        $this->stylesheets[$url] = true;
    }

    public function getStylesheets(): array
    {
        return array_keys($this->stylesheets);
    }

    public function clear(): void
    {
        $this->preloads = [];
        $this->stylesheets = [];
    }
}

