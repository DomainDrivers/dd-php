<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Employee;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Version;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\Set;

#[Entity]
#[Table('employees')]
final class Employee
{
    #[Id]
    #[Column(type: 'employee_id')]
    private EmployeeId $employeeId;

    #[Version]
    #[Column(type: 'bigint')]
    private int $version;

    #[Column(type: 'string')]
    private string $name;

    #[Column(type: 'string')]
    private string $lastName;

    #[Column(type: 'string', enumType: Seniority::class)]
    private Seniority $seniority;

    /**
     * @var Set<Capability> $capabilities
     */
    #[Column(type: 'capabilities', options: ['jsonb' => true])]
    private Set $capabilities;

    /**
     * @param Set<Capability> $capabilities
     */
    public function __construct(string $name, string $lastName, Seniority $seniority, Set $capabilities)
    {
        $this->employeeId = EmployeeId::newOne();
        $this->name = $name;
        $this->lastName = $lastName;
        $this->seniority = $seniority;
        $this->capabilities = $capabilities;
    }

    public function id(): EmployeeId
    {
        return $this->employeeId;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function seniority(): Seniority
    {
        return $this->seniority;
    }

    /**
     * @return Set<Capability>
     */
    public function capabilities(): Set
    {
        return $this->capabilities;
    }
}
