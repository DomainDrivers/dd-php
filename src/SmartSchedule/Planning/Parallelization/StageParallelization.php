<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use DomainDrivers\SmartSchedule\Sorter\FeedbackArcSetOnGraph;
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

    /**
     * @param Set<Stage> $stages
     */
    public function whatToRemove(Set $stages): RemovalSuggestion
    {
        $nodes = (new StagesToNodes())->calculate(GenericList::ofAll($stages->toArray()));

        return new RemovalSuggestion(FeedbackArcSetOnGraph::calculate(GenericList::ofAll($nodes->all()->toArray())));
    }
}
