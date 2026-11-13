<?php

declare(strict_types=1);

namespace CnA\View\Components;

class AlertComponent extends BaseComponent
{
    protected function defaultProps(): array
    {
        return [
            'title' => null,
            'message' => '',
            'variant' => 'info', // info, success, warning, danger
            'dismissible' => false,
            'class' => '',
            'icon' => null,
        ];
    }

    public function render(): string
    {
        $variant = $this->e($this->get('variant'));
        $extraClass = $this->e($this->get('class'));
        $title = $this->get('title') ? '<h4 class="cna-alert-title font-semibold mb-1">' . $this->e($this->get('title')) . '</h4>' : '';
        $message = '<div class="cna-alert-message text-sm">' . $this->e($this->get('message')) . '</div>';
        $icon = $this->get('icon') ? '<div class="cna-alert-icon mr-3">' . $this->e($this->get('icon')) . '</div>' : '';

        $dismiss = '';
        if ($this->get('dismissible')) {
            $dismiss = '<button type="button" class="cna-alert-close ml-auto" aria-label="Close" onclick="this.closest(\'.cna-alert\').remove()">&times;</button>';
        }

        $cssClass = trim("cna-alert cna-alert-{$variant} flex items-start p-4 rounded-lg {$extraClass}");

        return sprintf(
            '<div class="%s" role="alert">%s<div class="flex-1">%s%s</div>%s</div>',
            $cssClass,
            $icon,
            $title,
            $message,
            $dismiss
        );
    }
}

