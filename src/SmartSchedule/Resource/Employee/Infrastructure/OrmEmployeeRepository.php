<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Employee\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use DomainDrivers\SmartSchedule\Resource\Employee\Employee;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeId;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeRepository;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeSummary;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\GenericList;

final readonly class OrmEmployeeRepository implements EmployeeRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[\Override]
    public function save(Employee $employee): Employee
    {
        $this->entityManager->persist($employee);
        $this->entityManager->flush();

        return $employee;
    }

    #[\Override]
    public function findSummary(EmployeeId $employeeId): EmployeeSummary
    {
        $employee = $this->entityManager->find(Employee::class, $employeeId) ?? throw new \RuntimeException(sprintf('Employee %s not found', $employeeId->toString()));

        return new EmployeeSummary(
            $employee->id(),
            $employee->name(),
            $employee->lastName(),
            $employee->seniority(),
            $employee->capabilities()->filter(fn (Capability $c) => $c->isOfType('SKILL')),
            $employee->capabilities()->filter(fn (Capability $c) => $c->isOfType('PERMISSION')),
        );
    }

    #[\Override]
    public function findAllCapabilities(): GenericList
    {
        /** @var GenericList<Capability> $capabilities */
        $capabilities = GenericList::empty();
        foreach ($this->entityManager->getRepository(Employee::class)->findAll() as $employee) {
            $capabilities = $capabilities->appendAll($employee->capabilities());
        }

        /** @var GenericList<Capability> $capabilities */
        return $capabilities;
    }
}
