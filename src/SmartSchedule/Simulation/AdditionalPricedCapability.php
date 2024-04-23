<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use Decimal\Decimal;

final readonly class AdditionalPricedCapability
{
    public function __construct(public Decimal $value, public AvailableResourceCapability $availableResourceCapability)
    {
    }
}
