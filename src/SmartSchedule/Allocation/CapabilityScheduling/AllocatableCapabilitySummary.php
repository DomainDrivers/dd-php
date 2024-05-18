<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;

final readonly class AllocatableCapabilitySummary
{
    public function __construct(
        public AllocatableCapabilityId $id,
        public AllocatableResourceId $allocatableResourceId,
        public Capability $capability,
        public TimeSlot $timeSlot
    ) {
    }
}
