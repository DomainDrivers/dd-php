<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use DomainDrivers\SmartSchedule\Optimization\Item;
use DomainDrivers\SmartSchedule\Optimization\OptimizationFacade;
use DomainDrivers\SmartSchedule\Optimization\Result;
use DomainDrivers\SmartSchedule\Optimization\TotalCapacity;
use DomainDrivers\SmartSchedule\Optimization\TotalWeight;
use Munus\Collection\GenericList;

final readonly class SimulationFacade
{
    public function __construct(private OptimizationFacade $optimizationFacade)
    {
    }

    /**
     * @param GenericList<SimulatedProject> $projectsSimulations
     */
    public function whichProjectWithMissingDemandsIsMostProfitableToAllocateResourcesTo(
        GenericList $projectsSimulations,
        SimulatedCapabilities $totalCapability
    ): Result {
        return $this->optimizationFacade->calculate(
            $this->toItems($projectsSimulations),
            new TotalCapacity($totalCapability->capabilities)
        );
    }

    /**
     * @param GenericList<SimulatedProject> $projectsSimulations
     *
     * @return GenericList<Item>
     */
    private function toItems(GenericList $projectsSimulations): GenericList
    {
        return $projectsSimulations->map(fn (SimulatedProject $p) => new Item(
            $p->projectId->toString(),
            $p->earnings,
            new TotalWeight($p->missingDemands->all)
        ));
    }
}
