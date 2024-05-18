<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\Infrastructure;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableResourceId;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\UuidType;

final class AllocatableResourceIdType extends UuidType
{
    #[\Override]
    protected function fromString(string $value): \Stringable
    {
        return AllocatableResourceId::fromString($value);
    }
}
