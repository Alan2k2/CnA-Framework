<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Events\EventDispatcher;
use CnA\Testing\TestCase;

class UserCreatedEvent
{
    public function __construct(public string $email) {}
}

class EventDispatcherTest extends TestCase
{
    public function testEventListeningAndDispatching(): void
    {
        $dispatcher = new EventDispatcher();

        $handled = [];
        $dispatcher->listen(UserCreatedEvent::class, function (UserCreatedEvent $event) use (&$handled) {
            $handled[] = $event->email;
        });

        $event = new UserCreatedEvent('agent@cna.test');
        $dispatcher->dispatch($event);

        $this->assertEquals(['agent@cna.test'], $handled);
    }
}
