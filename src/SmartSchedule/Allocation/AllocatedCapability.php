<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Symfony\Component\Uid\Uuid;

final readonly class AllocatedCapability
{
    public function __construct(public Uuid $resourceId, public Capability $capability, public TimeSlot $timeSlot)
    {
    }
}
