<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Cashflow\Infrastructure;

use DomainDrivers\SmartSchedule\Allocation\Cashflow\Cost;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\DecimalType;

final class CostType extends DecimalType
{
    #[\Override]
    protected function fromString(string $value): \Stringable
    {
        return Cost::fromString($value);
    }
}
