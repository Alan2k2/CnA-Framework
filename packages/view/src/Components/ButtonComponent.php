<?php

declare(strict_types=1);

namespace CnA\View\Components;

class ButtonComponent extends BaseComponent
{
    protected function defaultProps(): array
    {
        return [
            'label' => 'Button',
            'variant' => 'primary', // primary, secondary, danger, success, outline, ghost
            'size' => 'medium', // small, medium, large
            'type' => 'button',
            'href' => null,
            'disabled' => false,
            'class' => '',
            'id' => null,
            'icon' => null,
        ];
    }

    public function render(): string
    {
        $label = $this->e($this->get('label'));
        $variant = $this->e($this->get('variant'));
        $size = $this->e($this->get('size'));
        $extraClass = $this->e($this->get('class'));
        $href = $this->get('href');
        $disabled = (bool) $this->get('disabled');
        $icon = $this->get('icon');
        $iconHtml = $icon ? '<span class="cna-btn-icon mr-2">' . $this->e($icon) . '</span>' : '';

        $cssClass = trim("cna-btn cna-btn-{$variant} cna-btn-{$size} {$extraClass}");

        if ($href !== null && !$disabled) {
            $attrs = [
                'href' => $href,
                'class' => $cssClass,
                'role' => 'button',
            ];
            if ($this->get('id')) {
                $attrs['id'] = $this->get('id');
            }
            return sprintf('<a%s>%s%s</a>', $this->buildAttributes($attrs), $iconHtml, $label);
        }

        $attrs = [
            'type' => $this->get('type', 'button'),
            'class' => $cssClass,
        ];
        if ($disabled) {
            $attrs['disabled'] = true;
            $attrs['aria-disabled'] = 'true';
        }
        if ($this->get('id')) {
            $attrs['id'] = $this->get('id');
        }

        return sprintf('<button%s>%s%s</button>', $this->buildAttributes($attrs), $iconHtml, $label);
    }
}

