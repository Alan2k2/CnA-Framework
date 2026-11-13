<?php

declare(strict_types=1);

namespace CnA\View\Components;

class ModalComponent extends BaseComponent
{
    protected function defaultProps(): array
    {
        return [
            'id' => 'cna_modal_' . uniqid(),
            'title' => 'Modal Dialog',
            'content' => '',
            'footer' => null,
            'size' => 'medium', // small, medium, large, full
            'class' => '',
        ];
    }

    public function render(): string
    {
        $id = $this->e($this->get('id'));
        $title = $this->e($this->get('title'));
        $content = $this->get('content');
        $footer = $this->get('footer');
        $size = $this->get('size', 'medium');
        $extraClass = $this->e($this->get('class'));

        $sizeClasses = match ($size) {
            'small' => 'max-w-md',
            'large' => 'max-w-4xl',
            'full' => 'max-w-full m-4',
            default => 'max-w-2xl',
        };

        $footerHtml = '';
        if ($footer !== null) {
            $footerHtml = '<div class="cna-modal-footer px-6 py-4 border-t border-slate-700/60 bg-slate-800/40 flex justify-end gap-3">' . $footer . '</div>';
        }

        return <<<HTML
<div id="{$id}" class="cna-modal fixed inset-0 z-50 hidden overflow-y-auto bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 {$extraClass}" role="dialog" aria-modal="true" aria-labelledby="{$id}_title">
    <div class="cna-modal-dialog w-full {$sizeClasses} bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl overflow-hidden transition-all transform">
        <div class="cna-modal-header px-6 py-4 border-b border-slate-700/60 flex items-center justify-between">
            <h3 id="{$id}_title" class="text-lg font-semibold text-white">{$title}</h3>
            <button type="button" class="cna-modal-close text-slate-400 hover:text-white" aria-label="Close" onclick="document.getElementById('{$id}').classList.add('hidden')">&times;</button>
        </div>
        <div class="cna-modal-body p-6 text-sm text-slate-300">
            {$content}
        </div>
        {$footerHtml}
    </div>
</div>
HTML;
    }
}
