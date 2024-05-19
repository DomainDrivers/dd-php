<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared;

interface Event
{
    public function occurredAt(): \DateTimeImmutable;
}
