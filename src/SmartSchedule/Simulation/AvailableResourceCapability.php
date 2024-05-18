<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use DomainDrivers\SmartSchedule\Optimization\CapacityDimension;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Symfony\Component\Uid\Uuid;

final readonly class AvailableResourceCapability implements CapacityDimension
{
    public function __construct(
        public Uuid $resourceId,
        public CapabilitySelector $capabilitySelector,
        public TimeSlot $timeSlot
    ) {
    }

    public function performs(Capability $capability): bool
    {
        return $this->capabilitySelector->canPerform($capability);
    }
}
