<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use Decimal\Decimal;
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
     */
    public function profitAfterBuyingNewCapability(
        GenericList $projectsSimulations,
        SimulatedCapabilities $capabilitiesWithoutNewOne,
        AdditionalPricedCapability $newPricedCapability
    ): Decimal {
        $capabilitiesWithNewResource = $capabilitiesWithoutNewOne->add($newPricedCapability->availableResourceCapability);
        $resultWithout = $this->optimizationFacade->calculate(
            $this->toItems($projectsSimulations),
            new TotalCapacity($capabilitiesWithoutNewOne->capabilities)
        );
        $resultWit = $this->optimizationFacade->calculate(
            $this->toItems($projectsSimulations),
            new TotalCapacity($capabilitiesWithNewResource->capabilities)
        );

        return $resultWit->profit->sub($newPricedCapability->value)->sub($resultWithout->profit);
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
            $p->calculateValue(),
            new TotalWeight($p->missingDemands->all)
        ));
    }
}
