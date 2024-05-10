<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Cashflow\Infrastructure;

use DomainDrivers\SmartSchedule\Allocation\Cashflow\Income;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\DecimalType;

final class IncomeType extends DecimalType
{
    #[\Override]
    protected function fromString(string $value): \Stringable
    {
        return Income::fromString($value);
    }
}
