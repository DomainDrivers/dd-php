<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use Decimal\Decimal;
use Munus\Collection\GenericList;
use Munus\Collection\Map;

final readonly class SimulationFacade
{
    /**
     * @param GenericList<SimulatedProject> $projectsSimulations
     */
    public function whichProjectWithMissingDemandsIsMostProfitableToAllocateResourcesTo(
        GenericList $projectsSimulations,
        SimulatedCapabilities $totalCapability
    ): Result {
        $allAvailabilities = $totalCapability->capabilities;
        $capacitiesSize = $allAvailabilities->length();
        /** @var Decimal[] $dp */
        $dp = [];
        /** @var GenericList<SimulatedProject>[] $chosenItemsList */
        $chosenItemsList = [];
        /** @var GenericList<AvailableResourceCapability>[] $allocatedCapacitiesList */
        $allocatedCapacitiesList = [];
        /** @var GenericList<AvailableResourceCapability> $emptyAllocation */
        $emptyAllocation = GenericList::empty();
        $itemToCapacitiesMap = Map::empty();

        $automaticallyIncludedItems = $projectsSimulations->filter(fn (SimulatedProject $p) => $p->allDemandsSatisfied());
        $guaranteedValue = $automaticallyIncludedItems
            ->map(fn (SimulatedProject $p) => $p->earnings)
            ->fold(new Decimal(0), fn (Decimal $sum, Decimal $e) => $sum->add($e));

        $projects = $projectsSimulations->toArray();
        uasort($projects, fn (SimulatedProject $a, SimulatedProject $b) => $a->earnings <=> $b->earnings);
        foreach (array_reverse($projects) as $project) {
            $chosenCapacities = $this->matchCapacities($project->missingDemands, $allAvailabilities);
            $allAvailabilities = $allAvailabilities->filter(fn (AvailableResourceCapability $c) => !$chosenCapacities->contains($c));

            if ($chosenCapacities->isEmpty()) {
                continue;
            }

            $sumValue = $project->earnings;
            $chosenCapacitiesCount = $chosenCapacities->length();

            for ($j = $capacitiesSize; $j >= $chosenCapacitiesCount; --$j) {
                if (($dp[$j] ?? new Decimal(0)) < $sumValue->add($dp[$j - $chosenCapacitiesCount] ?? new Decimal(0))) {
                    $dp[$j] = $sumValue->add($dp[$j - $chosenCapacitiesCount] ?? new Decimal(0));

                    $chosenItemsList[$j] = GenericList::ofAll($chosenItemsList[$j - $chosenCapacitiesCount] ?? [])
                        ->append($project);

                    $allocatedCapacitiesList[$j] = ($allocatedCapacitiesList[$j] ?? $emptyAllocation)->appendAll($chosenCapacities);
                }
            }
            $itemToCapacitiesMap->put($project->projectId->toString(), $chosenCapacities);
        }

        /** @var GenericList<SimulatedProject> $chosenProjects */
        $chosenProjects = $chosenItemsList[$capacitiesSize]->appendAll($automaticallyIncludedItems);

        return new Result(
            $dp[$capacitiesSize]->add($guaranteedValue),
            $chosenProjects,
            $itemToCapacitiesMap
        );
    }

    /**
     * @param GenericList<AvailableResourceCapability> $availableCapacities
     *
     * @return GenericList<AvailableResourceCapability>
     */
    private function matchCapacities(Demands $demands, GenericList $availableCapacities): GenericList
    {
        $result = GenericList::empty();
        foreach ($demands->all->toArray() as $singleDemand) {
            $matchingCapacity = $availableCapacities
                ->filter(fn (AvailableResourceCapability $arc) => $singleDemand->isSatisfiedBy($arc))
                ->getOrNull();
            if ($matchingCapacity !== null) {
                $result = $result->append($matchingCapacity);
            } else {
                return GenericList::empty();
            }
        }

        return $result;
    }
}
