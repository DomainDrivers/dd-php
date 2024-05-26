<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation\Cashflow;

use DomainDrivers\SmartSchedule\Allocation\Cashflow\Cashflow;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\CashflowRepository;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use Munus\Collection\GenericList;
use Munus\Collection\Map;
use Munus\Collection\Stream\Collectors;
use Munus\Control\Option;

final class InMemoryCashflowRepository implements CashflowRepository
{
    /**
     * @var Map<string, Cashflow>
     */
    private Map $cashflows;

    public function __construct()
    {
        $this->cashflows = Map::empty();
    }

    #[\Override]
    public function save(Cashflow $cashflow): void
    {
        $this->cashflows = $this->cashflows->put($cashflow->projectId()->toString(), $cashflow);
    }

    #[\Override]
    public function getById(ProjectAllocationsId $id): Cashflow
    {
        return $this->cashflows->get($id->toString())->getOrElseThrow(new \RuntimeException(sprintf('Cashflow %s not found', $id->toString())));
    }

    #[\Override]
    public function findById(ProjectAllocationsId $id): Option
    {
        return $this->cashflows->get($id->toString());
    }

    #[\Override]
    public function findAll(): GenericList
    {
        return $this->cashflows->values()->collect(Collectors::toList());
    }
}
