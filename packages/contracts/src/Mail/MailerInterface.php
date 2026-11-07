<?php

declare(strict_types=1);

namespace CnA\Contracts\Mail;

interface MailerInterface
{
    public function to(string|array $users): static;
    public function send(object $mailable): bool;
    public function queue(object $mailable): mixed;
    public function raw(string $text, callable $callback): bool;
}

