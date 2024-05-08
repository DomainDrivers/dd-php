<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation;

use Decimal\Decimal;
use DomainDrivers\SmartSchedule\Allocation\AllocatedCapability;
use DomainDrivers\SmartSchedule\Allocation\Allocations;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;

final class Project
{
    public Allocations $allocations;

    public function __construct(
        public ProjectAllocationsId $id,
        public Demands $demands,
        public Decimal $earnings
    ) {
        $this->allocations = Allocations::none();
    }

    public function add(AllocatedCapability $allocatedCapability): Allocations
    {
        $this->allocations = $this->allocations->add($allocatedCapability);

        return $this->allocations;
    }
}
