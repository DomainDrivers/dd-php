<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Infrastructure;

use DomainDrivers\SmartSchedule\Planning\ProjectId;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\UuidType;

final class ProjectIdType extends UuidType
{
    #[\Override]
    protected function fromString(string $value): \Stringable
    {
        return ProjectId::fromString($value);
    }
}
