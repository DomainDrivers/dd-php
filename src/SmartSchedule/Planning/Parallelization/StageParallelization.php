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
        return (new SortedNodesToParallelizedStages())(
            (new GraphTopologicalSort())(
                (new StagesToNodes())(GenericList::ofAll($stages->toArray()))
            )
        );
    }
}
