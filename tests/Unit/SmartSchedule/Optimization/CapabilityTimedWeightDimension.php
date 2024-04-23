<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Optimization;

use DomainDrivers\SmartSchedule\Optimization\CapacityDimension;
use DomainDrivers\SmartSchedule\Optimization\WeightDimension;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;

final readonly class CapabilityTimedWeightDimension implements WeightDimension
{
    public function __construct(
        public string $name,
        public string $type,
        public TimeSlot $timeSlot
    ) {
    }

    #[\Override]
    public function isSatisfiedBy(CapacityDimension $capacityDimension): bool
    {
        \assert($capacityDimension instanceof CapabilityTimedCapacityDimension);

        return $capacityDimension->capacityName === $this->name
            && $capacityDimension->capacityType === $this->type
            && $this->timeSlot->within($capacityDimension->timeSlot);
    }
}
