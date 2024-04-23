<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use DomainDrivers\SmartSchedule\Optimization\CapacityDimension;
use DomainDrivers\SmartSchedule\Optimization\WeightDimension;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;

final readonly class Demand implements WeightDimension
{
    private function __construct(public Capability $capability, public TimeSlot $timeSlot)
    {
    }

    public static function for(Capability $capability, TimeSlot $timeSlot): self
    {
        return new self($capability, $timeSlot);
    }

    #[\Override]
    public function isSatisfiedBy(CapacityDimension $capacityDimension): bool
    {
        \assert($capacityDimension instanceof AvailableResourceCapability);

        return $capacityDimension->performs($this->capability)
            && $this->timeSlot->within($capacityDimension->timeSlot);
    }
}
