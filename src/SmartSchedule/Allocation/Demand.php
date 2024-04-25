<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;

final readonly class Demand
{
    public function __construct(public Capability $capability, public TimeSlot $slot)
    {
    }
}
