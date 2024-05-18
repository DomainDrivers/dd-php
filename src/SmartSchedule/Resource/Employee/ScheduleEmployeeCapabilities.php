<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Employee;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityScheduler;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy\CompositePolicy;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy\DefaultPolicy;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy\OneOfSkills;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy\PermissionsInMultipleProjects;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;

final readonly class ScheduleEmployeeCapabilities
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private CapabilityScheduler $capabilityScheduler
    ) {
    }

    /**
     * @return GenericList<AllocatableCapabilityId>
     */
    public function setupEmployeeCapabilities(EmployeeId $employeeId, TimeSlot $timeSlot): GenericList
    {
        $summary = $this->employeeRepository->findSummary($employeeId);

        return $this->capabilityScheduler->scheduleResourceCapabilitiesForPeriod(
            $employeeId->toAllocatableResourceId(),
            $this->findAllocationPolicy($summary)->simultaneousCapabilitiesOf($summary),
            $timeSlot
        );
    }

    private function findAllocationPolicy(EmployeeSummary $employee): EmployeeAllocationPolicy
    {
        if ($employee->seniority === Seniority::LEAD) {
            return new CompositePolicy(GenericList::of(new OneOfSkills(), new PermissionsInMultipleProjects(3)));
        }

        return new DefaultPolicy();
    }
}
