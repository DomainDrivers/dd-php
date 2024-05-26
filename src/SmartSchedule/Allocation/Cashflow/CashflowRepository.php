<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Cashflow;

use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use Munus\Collection\GenericList;
use Munus\Control\Option;

interface CashflowRepository
{
    public function save(Cashflow $cashflow): void;

    public function getById(ProjectAllocationsId $id): Cashflow;

    /**
     * @return Option<Cashflow>
     */
    public function findById(ProjectAllocationsId $id): Option;

    /**
     * @return GenericList<Cashflow>
     */
    public function findAll(): GenericList;
}
