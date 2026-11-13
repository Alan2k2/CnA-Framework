<?php

declare(strict_types=1);

namespace CnA\FrontendVue;

use CnA\View\Renderer\HtmlEscaper;

class VueAdapter
{
    /**
     * Generate an accessible mount point container for a Vue component.
     *
     * @param string $component Component name (e.g. 'Dashboard')
     * @param array<string, mixed> $props Props to pass into the Vue root
     * @param string|null $id Optional DOM element ID
     * @param string $class Optional CSS classes
     * @return string
     */
    public static function mount(string $component, array $props = [], ?string $id = null, string $class = ''): string
    {
        $elementId = $id ?? 'cna_vue_' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $component)) . '_' . uniqid();
        $escapedId = HtmlEscaper::escape($elementId);
        $escapedComponent = HtmlEscaper::escape($component);
        $jsonProps = HtmlEscaper::escape(json_encode($props, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '{}');
        $escapedClass = HtmlEscaper::escape($class);

        return sprintf(
            '<div id="%s" class="cna-vue-root %s" data-cna-vue-component="%s" data-cna-vue-props="%s"></div>',
            $escapedId,
            $escapedClass,
            $escapedComponent,
            $jsonProps
        );
    }
}

