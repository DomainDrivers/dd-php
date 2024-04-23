<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Optimization;

use DomainDrivers\SmartSchedule\Optimization\CapacityDimension;
use Symfony\Component\Uid\Uuid;

final readonly class CapabilityCapacityDimension implements CapacityDimension
{
    public Uuid $uuid;

    public function __construct(
        public string $id,
        public string $capacityName,
        public string $capacityType
    ) {
        $this->uuid = Uuid::v7();
    }
}
