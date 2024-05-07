<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use Munus\Collection\GenericList;
use Munus\Collection\Stream\Collectors;

final class DurationCalculator
{
    /**
     * @param GenericList<Stage> $stages
     */
    public function apply(GenericList $stages): Duration
    {
        $parallelizedStages = (new StageParallelization())->of($stages->toStream()->collect(Collectors::toSet()));

        return $parallelizedStages->allSorted()->map(
            fn (ParallelStages $parallelStages) => new Duration($parallelStages->stages()
                ->map(fn (Stage $s) => $s->duration()->seconds)
                ->max()
                ->getOrElse(0))
        )->reduce(fn (Duration $sum, Duration $next) => $sum->plus($next));
    }
}
