<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Risk\Infrastructure;

use DomainDrivers\SmartSchedule\Risk\RiskPeriodicCheckSagaId;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\UuidType;

final class RiskPeriodicCheckSagaIdType extends UuidType
{
    #[\Override]
    protected function fromString(string $value): \Stringable
    {
        return RiskPeriodicCheckSagaId::fromString($value);
    }
}
