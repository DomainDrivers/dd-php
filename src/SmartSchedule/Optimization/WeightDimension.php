<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Optimization;

interface WeightDimension extends CapacityDimension
{
    public function isSatisfiedBy(CapacityDimension $capacityDimension): bool;
}
