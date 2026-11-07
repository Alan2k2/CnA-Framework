<?php

declare(strict_types=1);

namespace CnA\Notifications;

abstract class Notification
{
    /**
     * Get the notification's delivery channels.
     *
     * @return array<string>
     */
    abstract public function via(mixed $notifiable): array;

    public function toMail(mixed $notifiable): mixed
    {
        return null;
    }

    public function toArray(mixed $notifiable): array
    {
        return [];
    }
}

