<?php

declare(strict_types=1);

namespace CnA\Contracts\Plugins;

interface PluginManagerInterface
{
    /**
     * Register a plugin instance.
     */
    public function register(PluginInterface $plugin): void;

    /**
     * Get a registered plugin by name.
     */
    public function get(string $name): ?PluginInterface;

    /**
     * Check if a plugin is registered and active.
     */
    public function has(string $name): bool;

    /**
     * List all installed/registered plugins.
     *
     * @return array<string, PluginInterface>
     */
    public function all(): array;

    /**
     * Discover plugins from the filesystem or composer.
     */
    public function discover(): void;
}

