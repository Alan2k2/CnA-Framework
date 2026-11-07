<?php

declare(strict_types=1);

namespace CnA\Mail;

use CnA\Contracts\Logging\LoggerInterface;
use CnA\Contracts\Mail\MailerInterface;
use CnA\Contracts\Queue\QueueInterface;

class Mailer implements MailerInterface
{
    /** @var array<object> */
    protected array $sent = [];
    protected array $to = [];

    public function __construct(
        protected string $driver = 'array',
        protected ?LoggerInterface $logger = null,
        protected ?QueueInterface $queue = null
    ) {
    }

    public function to(string|array $users): static
    {
        $this->to = (array) $users;
        return $this;
    }

    public function send(object $mailable): bool
    {
        if (method_exists($mailable, 'build')) {
            $mailable->build();
        }

        if (!empty($this->to) && property_exists($mailable, 'to')) {
            $mailable->to = array_merge((array) $mailable->to, $this->to);
        }

        $this->sent[] = $mailable;

        if ($this->driver === 'log' && $this->logger !== null) {
            $subject = property_exists($mailable, 'subject') ? $mailable->subject : 'No Subject';
            $to = property_exists($mailable, 'to') ? implode(', ', (array) $mailable->to) : 'Unknown';
            $this->logger->info("Sending mail [{$subject}] to [{$to}]");
        }

        $this->to = [];
        return true;
    }

    public function queue(object $mailable): mixed
    {
        if ($this->queue !== null) {
            return $this->queue->push($mailable);
        }
        return $this->send($mailable);
    }

    public function raw(string $text, callable $callback): bool
    {
        $mailable = new class($text) extends Mailable {
            public function __construct(public string $body) {}
            public function build(): static { return $this; }
        };

        $callback($mailable);
        return $this->send($mailable);
    }

    public function sent(): array
    {
        return $this->sent;
    }

    public function hasSent(string $mailableClass): bool
    {
        foreach ($this->sent as $mail) {
            if ($mail instanceof $mailableClass) {
                return true;
            }
        }
        return false;
    }
}

