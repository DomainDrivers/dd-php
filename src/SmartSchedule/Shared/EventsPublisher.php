<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared;

interface EventsPublisher
{
    public function publishAfterCommit(Event $event): void;
}
