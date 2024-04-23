<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Optimization;

use DomainDrivers\SmartSchedule\Optimization\CapacityDimension;
use DomainDrivers\SmartSchedule\Optimization\WeightDimension;

final readonly class CapabilityWeightDimension implements WeightDimension
{
    public function __construct(public string $name, public string $type)
    {
    }

    #[\Override]
    public function isSatisfiedBy(CapacityDimension $capacityDimension): bool
    {
        \assert($capacityDimension instanceof CapabilityCapacityDimension);

        return $capacityDimension->capacityName === $this->name && $capacityDimension->capacityType === $this->type;
    }
}
