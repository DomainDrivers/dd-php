<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\Infrastructure;

use DomainDrivers\SmartSchedule\Shared\Event;
use DomainDrivers\SmartSchedule\Shared\EventsPublisher;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final readonly class MessengerEventPublisher implements EventsPublisher
{
    public function __construct(private MessageBusInterface $eventBus)
    {
    }

    #[\Override]
    public function publish(Event $event): void
    {
        $this->eventBus->dispatch($event, [new DispatchAfterCurrentBusStamp()]);
    }
}
