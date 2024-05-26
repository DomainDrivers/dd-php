<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\Infrastructure;

use DomainDrivers\SmartSchedule\Shared\EventsPublisher;
use DomainDrivers\SmartSchedule\Shared\PublishedEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final readonly class MessengerEventPublisher implements EventsPublisher
{
    public function __construct(private MessageBusInterface $eventBus)
    {
    }

    #[\Override]
    public function publish(PublishedEvent $event): void
    {
        $this->eventBus->dispatch($event, [new DispatchAfterCurrentBusStamp()]);
    }
}
