<?php

declare(strict_types=1);

namespace CnA\View\Components;

class InputComponent extends BaseComponent
{
    protected function defaultProps(): array
    {
        return [
            'name' => '',
            'label' => null,
            'type' => 'text',
            'value' => '',
            'placeholder' => '',
            'error' => null,
            'hint' => null,
            'required' => false,
            'disabled' => false,
            'readonly' => false,
            'class' => '',
            'id' => null,
        ];
    }

    public function render(): string
    {
        $name = $this->e($this->get('name'));
        $id = $this->get('id') ? $this->e($this->get('id')) : 'input_' . $name . '_' . uniqid();
        $label = $this->get('label');
        $error = $this->get('error');
        $hint = $this->get('hint');
        $extraClass = $this->e($this->get('class'));

        $labelHtml = '';
        if ($label !== null) {
            $req = $this->get('required') ? ' <span class="text-rose-500">*</span>' : '';
            $labelHtml = sprintf('<label for="%s" class="cna-input-label block text-xs font-semibold mb-1 text-slate-300">%s%s</label>', $id, $this->e($label), $req);
        }

        $inputAttrs = [
            'type' => $this->get('type', 'text'),
            'name' => $name,
            'id' => $id,
            'value' => (string) $this->get('value', ''),
            'placeholder' => (string) $this->get('placeholder', ''),
            'class' => trim("cna-input block w-full px-3 py-2 rounded-lg bg-slate-800/90 border " . ($error ? 'border-rose-500 focus:ring-rose-500' : 'border-slate-700 focus:border-cyan-500 focus:ring-cyan-500') . " text-sm text-white placeholder-slate-500 transition-all {$extraClass}"),
        ];

        if ($this->get('required')) {
            $inputAttrs['required'] = true;
            $inputAttrs['aria-required'] = 'true';
        }
        if ($this->get('disabled')) {
            $inputAttrs['disabled'] = true;
        }
        if ($this->get('readonly')) {
            $inputAttrs['readonly'] = true;
        }
        if ($error) {
            $inputAttrs['aria-invalid'] = 'true';
            $inputAttrs['aria-describedby'] = "{$id}_error";
        }

        $inputHtml = sprintf('<input%s>', $this->buildAttributes($inputAttrs));

        $feedbackHtml = '';
        if ($error) {
            $feedbackHtml = sprintf('<p id="%s_error" class="cna-input-error text-xs text-rose-400 mt-1" role="alert">%s</p>', $id, $this->e($error));
        } elseif ($hint) {
            $feedbackHtml = sprintf('<p class="cna-input-hint text-xs text-slate-400 mt-1">%s</p>', $this->e($hint));
        }

        return sprintf('<div class="cna-input-group mb-3">%s%s%s</div>', $labelHtml, $inputHtml, $feedbackHtml);
    }
}

