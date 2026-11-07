<?php

declare(strict_types=1);

namespace CnA\Notifications;

use CnA\Contracts\Container\ContainerInterface;
use CnA\Contracts\Mail\MailerInterface;
use CnA\Contracts\Notifications\NotificationChannelInterface;

class NotificationManager
{
    /** @var array<string, NotificationChannelInterface> */
    protected array $channels = [];

    public function __construct(
        protected ?ContainerInterface $container = null,
        protected ?MailerInterface $mailer = null
    ) {
    }

    public function registerChannel(string $name, NotificationChannelInterface $channel): void
    {
        $this->channels[$name] = $channel;
    }

    public function send(mixed $notifiables, Notification $notification): void
    {
        $notifiables = is_iterable($notifiables) ? $notifiables : [$notifiables];

        foreach ($notifiables as $notifiable) {
            $channels = $notification->via($notifiable);

            foreach ($channels as $channel) {
                if ($channel === 'mail' && $this->mailer !== null) {
                    $mailable = $notification->toMail($notifiable);
                    if ($mailable !== null) {
                        $this->mailer->to($notifiable->email ?? '')->send($mailable);
                    }
                } elseif (isset($this->channels[$channel])) {
                    $this->channels[$channel]->send($notifiable, $notification);
                }
            }
        }
    }
}

