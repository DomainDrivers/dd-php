<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Device\Infrastructure;

use DomainDrivers\SmartSchedule\Resource\Device\DeviceId;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\UuidType;

final class DeviceIdType extends UuidType
{
    #[\Override]
    protected function fromString(string $value): \Stringable
    {
        return DeviceId::fromString($value);
    }
}
