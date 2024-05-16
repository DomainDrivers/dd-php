<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Employee\Infrastructure;

use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeId;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\UuidType;

final class EmployeeIdType extends UuidType
{
    #[\Override]
    protected function fromString(string $value): \Stringable
    {
        return EmployeeId::fromString($value);
    }
}
