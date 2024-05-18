<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy;

use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeSummary;
use Munus\Collection\GenericList;
use Munus\Collection\Stream\Collectors;

final readonly class CompositePolicy implements EmployeeAllocationPolicy
{
    /**
     * @param GenericList<covariant EmployeeAllocationPolicy> $policies
     */
    public function __construct(private GenericList $policies)
    {
    }

    #[\Override]
    public function simultaneousCapabilitiesOf(EmployeeSummary $employee): GenericList
    {
        return $this->policies
            ->toStream()
            ->flatMap(fn (EmployeeAllocationPolicy $p) => $p->simultaneousCapabilitiesOf($employee)->toStream())
            ->collect(Collectors::toList());
    }
}
