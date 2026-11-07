<?php

declare(strict_types=1);

namespace CnA\Contracts\Notifications;

interface NotificationChannelInterface
{
    public function send(mixed $notifiable, object $notification): mixed;
}

