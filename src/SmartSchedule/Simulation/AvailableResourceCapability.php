<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use Symfony\Component\Uid\Uuid;

final readonly class AvailableResourceCapability
{
    public function __construct(
        public Uuid $resourceId,
        public Capability $capability,
        public TimeSlot $timeSlot
    ) {
    }

    public function performs(Capability $capability): bool
    {
        return $this->capability->equals($capability);
    }
}
