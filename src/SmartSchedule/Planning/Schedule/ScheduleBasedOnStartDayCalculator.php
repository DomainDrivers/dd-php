<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Schedule;

use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStagesList;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Map;

final readonly class ScheduleBasedOnStartDayCalculator
{
    /**
     * @return Map<string, TimeSlot>
     */
    public function calculate(\DateTimeImmutable $startDate, ParallelStagesList $parallelStages, callable $comparator): Map
    {
        $scheduleMap = Map::empty();
        $currentStart = $startDate;
        foreach ($parallelStages->allSorted($comparator)->toArray() as $stages) {
            $parallelizedStagesEnd = $currentStart;
            foreach ($stages->stages()->toArray() as $stage) {
                $stageEnd = $currentStart->add($stage->duration()->toDateInterval());
                $scheduleMap = $scheduleMap->put($stage->name(), new TimeSlot($currentStart, $stageEnd));
                if ($stageEnd > $parallelizedStagesEnd) {
                    $parallelizedStagesEnd = $stageEnd;
                }
            }
            $currentStart = $parallelizedStagesEnd;
        }

        return $scheduleMap;
    }
}
