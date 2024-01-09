<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use Munus\Collection\Set;

final class StageParallelization
{
    /**
     * @param Set<Stage> $stages
     */
    public function of(Set $stages): ParallelStagesList
    {
        return ParallelStagesList::empty();
    }
}
