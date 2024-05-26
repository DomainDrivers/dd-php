<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared;

interface EventsPublisher
{
    // remember about transactions scope
    public function publish(PublishedEvent $event): void;
}
