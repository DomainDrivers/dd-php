<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Employee;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use Munus\Collection\Stream\Collectors;

final readonly class EmployeeFacade
{
    public function __construct(private EmployeeRepository $employeeRepository)
    {
    }

    public function findEmployee(EmployeeId $employeeId): EmployeeSummary
    {
        return $this->employeeRepository->findSummary($employeeId);
    }

    /**
     * @return GenericList<Capability>
     */
    public function findAllCapabilities(): GenericList
    {
        return $this->employeeRepository->findAllCapabilities();
    }

    /**
     * @param Set<Capability> $skills
     * @param Set<Capability> $permissions
     */
    public function addEmployee(string $name, string $lastName, Seniority $seniority, Set $skills, Set $permissions): EmployeeId
    {
        return $this->employeeRepository->save(new Employee(
            $name,
            $lastName,
            $seniority,
            $skills->toStream()->appendAll($permissions)->collect(Collectors::toSet())
        ))->id();
    }

    // add vacation
    // calls availability
    // add sick leave
    // calls availability
    // change skills
}
