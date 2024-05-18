<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\Infrastructure;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\UuidType;

final class AllocatableCapabilityIdType extends UuidType
{
    #[\Override]
    protected function fromString(string $value): \Stringable
    {
        return AllocatableCapabilityId::fromString($value);
    }
}
