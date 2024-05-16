<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Employee;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\GenericList;

interface EmployeeRepository
{
    public function save(Employee $employee): Employee;

    public function findSummary(EmployeeId $employeeId): EmployeeSummary;

    /**
     * @return GenericList<Capability>
     */
    public function findAllCapabilities(): GenericList;
}
