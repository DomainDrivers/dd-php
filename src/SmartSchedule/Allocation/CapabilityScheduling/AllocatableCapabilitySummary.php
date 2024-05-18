<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;

final readonly class AllocatableCapabilitySummary
{
    public function __construct(
        public AllocatableCapabilityId $id,
        public AllocatableResourceId $allocatableResourceId,
        public CapabilitySelector $capabilities,
        public TimeSlot $timeSlot
    ) {
    }
}
