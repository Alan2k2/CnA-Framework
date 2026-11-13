<?php

declare(strict_types=1);

namespace CnA\View\Components;

use CnA\Contracts\View\ComponentInterface;
use InvalidArgumentException;

class ComponentRegistry
{
    /** @var array<string, class-string<ComponentInterface>> */
    protected array $components = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    /**
     * Register default accessible CnA components.
     */
    protected function registerDefaults(): void
    {
        $this->register('ui.button', ButtonComponent::class);
        $this->register('ui.alert', AlertComponent::class);
        $this->register('ui.badge', BadgeComponent::class);
        $this->register('ui.card', CardComponent::class);
        $this->register('ui.input', InputComponent::class);
        $this->register('ui.modal', ModalComponent::class);
        $this->register('ui.table', TableComponent::class);
    }

    /**
     * Register a component class for an alias.
     *
     * @param string $name
     * @param class-string<ComponentInterface> $class
     */
    public function register(string $name, string $class): void
    {
        if (!is_subclass_of($class, ComponentInterface::class)) {
            throw new InvalidArgumentException("Class [{$class}] must implement " . ComponentInterface::class);
        }
        $this->components[$name] = $class;
    }

    /**
     * Check if a component alias is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->components[$name]);
    }

    /**
     * Resolve and instantiate a component by name and props.
     *
     * @param string $name
     * @param array<string, mixed> $props
     * @return ComponentInterface|null
     */
    public function resolve(string $name, array $props = []): ?ComponentInterface
    {
        if (!isset($this->components[$name])) {
            return null;
        }

        $class = $this->components[$name];
        return new $class($props);
    }

    /**
     * Get all registered component names.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->components;
    }
}

