<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy;

use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeSummary;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use Munus\Collection\GenericList;
use Munus\Collection\Stream\Collectors;

final class DefaultPolicy implements EmployeeAllocationPolicy
{
    #[\Override]
    public function simultaneousCapabilitiesOf(EmployeeSummary $employee): GenericList
    {
        return GenericList::of(CapabilitySelector::canPerformOneOf($employee->skills
            ->toStream()
            ->appendAll($employee->permissions)
            ->collect(Collectors::toSet())
        ));
    }
}
