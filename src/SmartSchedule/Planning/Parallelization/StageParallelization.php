<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use Munus\Collection\GenericList;
use Munus\Collection\Set;
use Munus\Collection\Stream\Collectors;

final class StageParallelization
{
    /**
     * @param Set<Stage> $stages
     */
    public function of(Set $stages): ParallelStagesList
    {
        return $this->createSortedNodesRecursively($stages, ParallelStagesList::empty());
    }

    /**
     * @param Set<Stage> $remainingNodes
     */
    private function createSortedNodesRecursively(Set $remainingNodes, ParallelStagesList $accumulatedSortedNodes): ParallelStagesList
    {
        $alreadyProcessedNodes = $accumulatedSortedNodes->all()
            ->toStream()
            ->flatMap(fn (ParallelStages $stages) => $stages->stages()->toStream())
            ->collect(Collectors::toList());
        $nodesWithoutDependencies = $this->withAllDependenciesPresentIn($remainingNodes, $alreadyProcessedNodes);

        if ($nodesWithoutDependencies->isEmpty()) {
            return $accumulatedSortedNodes;
        }

        $newSortedNodes = $accumulatedSortedNodes->add(new ParallelStages($nodesWithoutDependencies));
        $newRemainingNodes = $remainingNodes->removeAll($nodesWithoutDependencies);

        return $this->createSortedNodesRecursively($newRemainingNodes, $newSortedNodes);
    }

    /**
     * @param Set<Stage>         $toCheck
     * @param GenericList<Stage> $presentIn
     *
     * @return Set<Stage>
     */
    private function withAllDependenciesPresentIn(Set $toCheck, GenericList $presentIn): Set
    {
        return $toCheck
            ->filter(fn (Stage $n) => $presentIn->containsAll($n->dependencies()))
            ->collect(Collectors::toSet());
    }
}
