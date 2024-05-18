<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy;

use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeSummary;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use Munus\Collection\GenericList;
use Munus\Collection\Stream;
use Munus\Collection\Stream\Collectors;

final readonly class PermissionsInMultipleProjects implements EmployeeAllocationPolicy
{
    public function __construct(private int $howMany)
    {
    }

    #[\Override]
    public function simultaneousCapabilitiesOf(EmployeeSummary $employee): GenericList
    {
        return $employee->permissions
            ->toStream()
            ->flatMap(fn (Capability $permission) => Stream::range(1, $this->howMany)->map(fn () => $permission))
            ->map(fn (Capability $permission) => CapabilitySelector::canJustPerform($permission))
            ->collect(Collectors::toList())
        ;
    }
}
