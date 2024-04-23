<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Optimization;

use DomainDrivers\SmartSchedule\Optimization\CapacityDimension;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Symfony\Component\Uid\Uuid;

final readonly class CapabilityTimedCapacityDimension implements CapacityDimension
{
    public Uuid $uuid;

    public function __construct(
        public string $id,
        public string $capacityName,
        public string $capacityType,
        public TimeSlot $timeSlot
    ) {
        $this->uuid = Uuid::v7();
    }
}
