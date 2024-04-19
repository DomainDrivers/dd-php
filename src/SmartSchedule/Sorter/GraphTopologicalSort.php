<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Sorter;

use Munus\Collection\Stream\Collectors;

final readonly class GraphTopologicalSort
{
    public function sort(Nodes $nodes): SortedNodes
    {
        return $this->createSortedNodesRecursively($nodes, SortedNodes::empty());
    }

    private function createSortedNodesRecursively(Nodes $remainingNodes, SortedNodes $accumulatedSortedNodes): SortedNodes
    {
        $alreadyProcessedNodes = $accumulatedSortedNodes->all
            ->toStream()
            ->flatMap(fn (Nodes $n) => $n->all()->toStream())
            ->collect(Collectors::toList());

        $nodesWithoutDependencies = $remainingNodes->withAllDependenciesPresentIn($alreadyProcessedNodes);

        if ($nodesWithoutDependencies->all()->isEmpty()) {
            return $accumulatedSortedNodes;
        }

        $newSortedNodes = $accumulatedSortedNodes->add($nodesWithoutDependencies);
        $remainingNodes = $remainingNodes->removeAll($nodesWithoutDependencies->all());

        return $this->createSortedNodesRecursively($remainingNodes, $newSortedNodes);
    }
}
