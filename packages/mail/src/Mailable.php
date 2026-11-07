<?php

declare(strict_types=1);

namespace CnA\Mail;

abstract class Mailable
{
    public array $to = [];
    public ?string $subject = null;
    public string $view = '';
    public array $viewData = [];
    public array $attachments = [];

    public function to(string|array $address): static
    {
        $this->to = (array) $address;
        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function view(string $view, array $data = []): static
    {
        $this->view = $view;
        $this->viewData = $data;
        return $this;
    }

    public function attach(string $file, array $options = []): static
    {
        $this->attachments[] = ['file' => $file, 'options' => $options];
        return $this;
    }

    abstract public function build(): static;
}

