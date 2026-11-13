<?php

declare(strict_types=1);

namespace CnA\Contracts\Plugins;

use CnA\Contracts\Core\ApplicationInterface;

interface PluginInterface
{
    /**
     * Unique plugin identifier (vendor/plugin-name).
     */
    public function getName(): string;

    /**
     * Semantic version string.
     */
    public function getVersion(): string;

    /**
     * Human-readable description.
     */
    public function getDescription(): string;

    /**
     * Bootstrap the plugin with the application.
     */
    public function register(ApplicationInterface $app): void;

    /**
     * Hook called on plugin activation.
     */
    public function boot(ApplicationInterface $app): void;
}
