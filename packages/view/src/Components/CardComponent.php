<?php

declare(strict_types=1);

namespace CnA\View\Components;

class CardComponent extends BaseComponent
{
    protected function defaultProps(): array
    {
        return [
            'title' => null,
            'subtitle' => null,
            'content' => '',
            'footer' => null,
            'class' => '',
            'id' => null,
        ];
    }

    public function render(): string
    {
        $extraClass = $this->e($this->get('class'));
        $title = $this->get('title');
        $subtitle = $this->get('subtitle');
        $content = $this->get('content');
        $footer = $this->get('footer');

        $headerHtml = '';
        if ($title !== null || $subtitle !== null) {
            $titleHtml = $title ? '<h3 class="cna-card-title text-lg font-semibold">' . $this->e($title) . '</h3>' : '';
            $subtitleHtml = $subtitle ? '<p class="cna-card-subtitle text-xs text-slate-400 mt-1">' . $this->e($subtitle) . '</p>' : '';
            $headerHtml = '<div class="cna-card-header p-4 border-b border-slate-700/50">' . $titleHtml . $subtitleHtml . '</div>';
        }

        $bodyHtml = '<div class="cna-card-body p-5">' . (is_string($content) ? $content : '') . '</div>';

        $footerHtml = '';
        if ($footer !== null) {
            $footerHtml = '<div class="cna-card-footer p-4 border-t border-slate-700/50 bg-slate-800/30">' . $footer . '</div>';
        }

        $attrs = [
            'class' => trim("cna-card rounded-xl border border-slate-700/60 bg-slate-900/80 shadow-lg overflow-hidden {$extraClass}"),
        ];
        if ($this->get('id')) {
            $attrs['id'] = $this->get('id');
        }

        return sprintf('<div%s>%s%s%s</div>', $this->buildAttributes($attrs), $headerHtml, $bodyHtml, $footerHtml);
    }
}
