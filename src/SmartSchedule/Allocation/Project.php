<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use Decimal\Decimal;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Control\Option;

final class Project
{
    private Allocations $allocations;

    public function __construct(private readonly Demands $demands, private readonly Decimal $earnings)
    {
        $this->allocations = Allocations::none();
    }

    public function missingDemands(): Demands
    {
        return $this->demands->missingDemands($this->allocations);
    }

    public function earnings(): Decimal
    {
        return $this->earnings;
    }

    /**
     * @return Option<AllocatedCapability>
     */
    public function remove(AllocatedCapability $capability, TimeSlot $forSlot): Option
    {
        $toRemove = $this->allocations->find($capability->allocatedCapabilityID);
        if ($toRemove->isEmpty()) {
            return $toRemove;
        }
        $this->allocations = $this->allocations->remove($capability->allocatedCapabilityID, $forSlot);

        return $toRemove;
    }

    public function add(AllocatedCapability $allocatedCapability): Allocations
    {
        $this->allocations = $this->allocations->add($allocatedCapability);

        return $this->allocations;
    }
}
