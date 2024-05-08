<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Value\Comparable;
use Symfony\Component\Uid\Uuid;

final readonly class AllocatedCapability implements Comparable
{
    public function __construct(
        public Uuid $allocatedCapabilityID,
        public Uuid $resourceId,
        public Capability $capability,
        public TimeSlot $timeSlot
    ) {
    }

    public static function new(
        Uuid $resourceId,
        Capability $capability,
        TimeSlot $timeSlot
    ): self {
        return new self(Uuid::v7(), $resourceId, $capability, $timeSlot);
    }

    #[\Override]
    public function equals(Comparable $other): bool
    {
        return self::class === $other::class
            && $this->resourceId->equals($other->resourceId)
            && $this->capability->equals($other->capability)
            && $this->timeSlot->equals($other->timeSlot);
    }
}
