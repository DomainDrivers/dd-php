<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Infrastructure;

use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\UuidType;

final class ProjectAllocationsIdType extends UuidType
{
    #[\Override]
    protected function fromString(string $value): \Stringable
    {
        return ProjectAllocationsId::fromString($value);
    }
}
