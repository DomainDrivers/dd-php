<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Value\Comparable;

final readonly class AllocatedCapability implements Comparable
{
    public function __construct(
        public AllocatableCapabilityId $allocatedCapabilityID,
        public CapabilitySelector $capability,
        public TimeSlot $timeSlot
    ) {
    }

    #[\Override]
    public function equals(Comparable $other): bool
    {
        return self::class === $other::class
            && $this->allocatedCapabilityID->id->equals($other->allocatedCapabilityID->id)
            && $this->capability->equals($other->capability)
            && $this->timeSlot->equals($other->timeSlot);
    }
}
