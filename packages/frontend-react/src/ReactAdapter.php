<?php

declare(strict_types=1);

namespace CnA\FrontendReact;

use CnA\View\Renderer\HtmlEscaper;

class ReactAdapter
{
    /**
     * Generate an accessible mount point container for a React component.
     *
     * @param string $component Component name (e.g. 'Dashboard')
     * @param array<string, mixed> $props Props to pass into the React root
     * @param string|null $id Optional DOM element ID
     * @param string $class Optional CSS classes
     * @return string
     */
    public static function mount(string $component, array $props = [], ?string $id = null, string $class = ''): string
    {
        $elementId = $id ?? 'cna_react_' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $component)) . '_' . uniqid();
        $escapedId = HtmlEscaper::escape($elementId);
        $escapedComponent = HtmlEscaper::escape($component);
        $jsonProps = HtmlEscaper::escape(json_encode($props, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '{}');
        $escapedClass = HtmlEscaper::escape($class);

        return sprintf(
            '<div id="%s" class="cna-react-root %s" data-cna-react-component="%s" data-cna-react-props="%s"></div>',
            $escapedId,
            $escapedClass,
            $escapedComponent,
            $jsonProps
        );
    }
}
