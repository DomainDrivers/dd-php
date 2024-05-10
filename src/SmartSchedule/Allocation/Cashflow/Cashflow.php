<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Cashflow;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;

#[Entity]
#[Table(name: 'cashflows')]
final class Cashflow
{
    #[Id]
    #[Column(type: 'project_allocations_id')]
    private ProjectAllocationsId $projectId;

    #[Column(type: 'income')]
    private Income $income;

    #[Column(type: 'cost')]
    private Cost $cost;

    public function __construct(ProjectAllocationsId $projectId)
    {
        $this->projectId = $projectId;
        $this->income = Income::of(0);
        $this->cost = Cost::of(0);
    }

    public function earnings(): Earnings
    {
        return $this->income->minus($this->cost);
    }

    public function update(Income $income, Cost $cost): void
    {
        $this->income = $income;
        $this->cost = $cost;
    }
}
