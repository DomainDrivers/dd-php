<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use DomainDrivers\SmartSchedule\Optimization\CapacityDimension;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Symfony\Component\Uid\Uuid;

final readonly class AvailableResourceCapability implements CapacityDimension
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
