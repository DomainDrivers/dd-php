<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Cashflow;

use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;

final readonly class CashFlowFacade
{
    public function __construct(private CashflowRepository $cashflowRepository)
    {
    }

    public function addIncomeAndCost(ProjectAllocationsId $projectId, Income $income, Cost $cost): void
    {
        $cashflow = $this->cashflowRepository->findById($projectId)->getOrElse(new Cashflow($projectId));
        $cashflow->update($income, $cost);
        $this->cashflowRepository->save($cashflow);
    }

    public function find(ProjectAllocationsId $projectId): Earnings
    {
        return $this->cashflowRepository->getById($projectId)->earnings();
    }
}
