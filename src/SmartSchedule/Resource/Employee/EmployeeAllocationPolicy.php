<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Employee;

use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use Munus\Collection\GenericList;

interface EmployeeAllocationPolicy
{
    /**
     * @return GenericList<CapabilitySelector>
     */
    public function simultaneousCapabilitiesOf(EmployeeSummary $employee): GenericList;
}
