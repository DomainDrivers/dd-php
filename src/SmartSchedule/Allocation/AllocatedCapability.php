<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Symfony\Component\Uid\Uuid;

final readonly class AllocatedCapability
{
    public function __construct(
        public Uuid $allocatedCapabilityID,
        public Uuid $resourceId,
        public Capability $capability,
        public TimeSlot $timeSlot
    ) {
    }

    public static function with(
        Uuid $resourceId,
        Capability $capability,
        TimeSlot $timeSlot
    ): self {
        return new self(Uuid::v7(), $resourceId, $capability, $timeSlot);
    }
}
