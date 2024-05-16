<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use DomainDrivers\SmartSchedule\Sorter\Node;
use DomainDrivers\SmartSchedule\Sorter\Nodes;
use DomainDrivers\SmartSchedule\Sorter\SortedNodes;

final readonly class SortedNodesToParallelizedStages
{
    /**
     * @param SortedNodes<Stage> $sortedNodes
     */
    public function __invoke(SortedNodes $sortedNodes): ParallelStagesList
    {
        return new ParallelStagesList($sortedNodes->all
            ->map(fn (Nodes $nodes) => new ParallelStages($nodes->all()
                ->map(fn (Node $node): Stage => $node->content)
            ))
        );
    }
}
