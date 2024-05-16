<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Employee;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\Set;

final readonly class EmployeeSummary
{
    /**
     * @param Set<Capability> $skills
     * @param Set<Capability> $permissions
     */
    public function __construct(
        public EmployeeId $id,
        public string $name,
        public string $lastName,
        public Seniority $seniority,
        public Set $skills,
        public Set $permissions
    ) {
    }
}
