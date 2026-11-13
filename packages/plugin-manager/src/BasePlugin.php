<?php

declare(strict_types=1);

namespace CnA\Plugins;

use CnA\Contracts\Core\ApplicationInterface;
use CnA\Contracts\Plugins\PluginInterface;

abstract class BasePlugin implements PluginInterface
{
    protected string $name = '';
    protected string $version = '1.0.0';
    protected string $description = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function register(ApplicationInterface $app): void
    {
    }

    public function boot(ApplicationInterface $app): void
    {
    }
}

