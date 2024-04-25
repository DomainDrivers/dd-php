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
        $parallelStages->allSorted($comparator);
        // todo

        return $scheduleMap;
    }
}
