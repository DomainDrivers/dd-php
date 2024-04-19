<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use DomainDrivers\SmartSchedule\Sorter\GraphTopologicalSort;
use Munus\Collection\GenericList;
use Munus\Collection\Set;

final class StageParallelization
{
    /**
     * @param Set<Stage> $stages
     */
    public function of(Set $stages): ParallelStagesList
    {
        $nodes = (new StagesToNodes())->calculate(GenericList::ofAll($stages->toArray()));
        $sortedNodes = (new GraphTopologicalSort())->sort($nodes);

        return (new SortedNodesToParallelizedStages())->calculate($sortedNodes);
    }
}
