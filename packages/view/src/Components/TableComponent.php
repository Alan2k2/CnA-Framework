<?php

declare(strict_types=1);

namespace CnA\View\Components;

class TableComponent extends BaseComponent
{
    protected function defaultProps(): array
    {
        return [
            'headers' => [],
            'rows' => [],
            'striped' => true,
            'hoverable' => true,
            'class' => '',
            'empty' => 'No records found.',
        ];
    }

    public function render(): string
    {
        $headers = (array) $this->get('headers', []);
        $rows = (array) $this->get('rows', []);
        $striped = (bool) $this->get('striped');
        $hoverable = (bool) $this->get('hoverable');
        $extraClass = $this->e($this->get('class'));
        $emptyText = $this->e($this->get('empty'));

        $thead = '';
        if (!empty($headers)) {
            $thHtml = '';
            foreach ($headers as $header) {
                $thHtml .= '<th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">' . $this->e($header) . '</th>';
            }
            $thead = '<thead class="bg-slate-800/80 border-b border-slate-700"><tr>' . $thHtml . '</tr></thead>';
        }

        $tbody = '';
        if (empty($rows)) {
            $colSpan = max(1, count($headers));
            $tbody = '<tbody><tr><td colspan="' . $colSpan . '" class="px-4 py-8 text-center text-sm text-slate-400">' . $emptyText . '</td></tr></tbody>';
        } else {
            $trHtml = '';
            foreach ($rows as $index => $row) {
                $bg = ($striped && $index % 2 === 1) ? 'bg-slate-800/40' : 'bg-transparent';
                $hover = $hoverable ? 'hover:bg-cyan-950/20 transition-colors' : '';
                $tdHtml = '';
                foreach ($row as $cell) {
                    $tdHtml .= '<td class="px-4 py-3 text-sm text-slate-200 border-b border-slate-800">' . (is_string($cell) ? $cell : $this->e($cell)) . '</td>';
                }
                $trHtml .= "<tr class=\"{$bg} {$hover}\">{$tdHtml}</tr>";
            }
            $tbody = '<tbody>' . $trHtml . '</tbody>';
        }

        return <<<HTML
<div class="cna-table-wrapper w-full overflow-x-auto rounded-xl border border-slate-800 shadow-md {$extraClass}">
    <table class="cna-table w-full text-left border-collapse">
        {$thead}
        {$tbody}
    </table>
</div>
HTML;
    }
}
