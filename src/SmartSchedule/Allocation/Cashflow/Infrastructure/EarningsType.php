<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Cashflow\Infrastructure;

use DomainDrivers\SmartSchedule\Allocation\Cashflow\Earnings;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\DecimalType;

final class EarningsType extends DecimalType
{
    #[\Override]
    protected function fromString(string $value): \Stringable
    {
        return Earnings::fromString($value);
    }
}
