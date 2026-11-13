<?php

declare(strict_types=1);

namespace CnA\View\Components;

class BadgeComponent extends BaseComponent
{
    protected function defaultProps(): array
    {
        return [
            'label' => '',
            'variant' => 'primary', // primary, secondary, success, danger, warning, info
            'pill' => false,
            'class' => '',
        ];
    }

    public function render(): string
    {
        $label = $this->e($this->get('label'));
        $variant = $this->e($this->get('variant'));
        $pill = $this->get('pill') ? 'rounded-full' : 'rounded';
        $extraClass = $this->e($this->get('class'));

        $cssClass = trim("cna-badge cna-badge-{$variant} inline-flex items-center px-2.5 py-0.5 text-xs font-medium {$pill} {$extraClass}");

        return sprintf('<span class="%s">%s</span>', $cssClass, $label);
    }
}
