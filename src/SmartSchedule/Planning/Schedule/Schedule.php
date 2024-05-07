<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Schedule;

use DomainDrivers\SmartSchedule\Availability\Calendars;
use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStages;
use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStagesList;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Map;

final readonly class Schedule
{
    /**
     * @param Map<string, TimeSlot> $dates
     */
    public function __construct(public Map $dates)
    {
    }

    public static function none(): self
    {
        return new self(Map::empty());
    }

    public static function basedOnStartDay(\DateTimeImmutable $startDate, ParallelStagesList $parallelizedStages): self
    {
        return new self((new ScheduleBasedOnStartDayCalculator())->calculate($startDate, $parallelizedStages, fn (ParallelStages $a, ParallelStages $b): int => $a->print() <=> $b->print()));
    }

    public static function basedOnReferenceStageTimeSlot(Stage $referenceStage, TimeSlot $stageProposedTimeSlot, ParallelStagesList $parallelizedStages): self
    {
        return new self((new ScheduleBasedOnReferenceStageCalculator())->calculate($referenceStage, $stageProposedTimeSlot, $parallelizedStages, fn (ParallelStages $a, ParallelStages $b): int => $a->print() <=> $b->print()));
    }

    /**
     * @param GenericList<Stage> $stages
     */
    public static function basedOnChosenResourcesAvailability(Calendars $chosenResourcesCalendars, GenericList $stages): self
    {
        return new self((new ScheduleBasedOnChosenResourcesAvailabilityCalculator())->calculate($chosenResourcesCalendars, $stages));
    }
}
