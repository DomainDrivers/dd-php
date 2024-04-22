<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use Decimal\Decimal;
use Munus\Collection\GenericList;
use Munus\Collection\Map;

final readonly class SimulationFacade
{
    /**
     * @param GenericList<SimulatedProject> $projects
     */
    public function whichProjectWithMissingDemandsIsMostProfitableToAllocateResourcesTo(
        GenericList $projects,
        SimulatedCapabilities $totalCapability
    ): Result {
        return new Result(new Decimal(0), GenericList::empty(), Map::empty());
    }
}
