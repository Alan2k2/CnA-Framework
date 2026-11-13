<?php

declare(strict_types=1);

namespace CnA\View\Renderer;

use CnA\Contracts\View\ComponentInterface;
use CnA\View\Components\ComponentRegistry;
use CnA\View\Engine\ViewEngine;
use InvalidArgumentException;

class ComponentRenderer
{
    public function __construct(
        protected ComponentRegistry $registry,
        protected ?ViewEngine $viewEngine = null
    ) {
    }

    /**
     * Render a component by name and props.
     *
     * @param string|ComponentInterface $component
     * @param array<string, mixed> $props
     * @return string
     */
    public function render(string|ComponentInterface $component, array $props = []): string
    {
        if ($component instanceof ComponentInterface) {
            return $component->render();
        }

        // Check if registered in ComponentRegistry
        if ($this->registry->has($component)) {
            $instance = $this->registry->resolve($component, $props);
            if ($instance !== null) {
                return $instance->render();
            }
        }

        // Check if a view template exists under components/{name}
        if ($this->viewEngine !== null) {
            $viewCandidate = 'components.' . str_replace('/', '.', $component);
            if ($this->viewEngine->exists($viewCandidate)) {
                return $this->viewEngine->render($viewCandidate, $props);
            }
        }

        throw new InvalidArgumentException("Unable to resolve component [{$component}].");
    }
}
