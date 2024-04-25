<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Schedule;

use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStages;
use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStagesList;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Map;

final readonly class ScheduleBasedOnReferenceStageCalculator
{
    /**
     * @param callable(ParallelStages, ParallelStages): int $comparator
     *
     * @return Map<string, TimeSlot>
     */
    public function calculate(
        Stage $referenceStage,
        TimeSlot $referenceStageProposedTimeSlot,
        ParallelStagesList $parallelizedStages,
        callable $comparator
    ): Map {
        $all = $parallelizedStages->allSorted($comparator);
        $referenceStageIndex = $this->findReferenceStageIndex($referenceStage, $all);
        if ($referenceStageIndex === -1) {
            return Map::empty();
        }
        $scheduleMap = Map::empty();
        $stagesBeforeReference = $all->take($referenceStageIndex);
        $stagesAfterReference = $all->drop($referenceStageIndex + 1);
        $scheduleMap = $this->calculateStagesBeforeCritical($stagesBeforeReference, $referenceStageProposedTimeSlot, $scheduleMap);
        $scheduleMap = $this->calculateStagesAfterCritical($stagesAfterReference, $referenceStageProposedTimeSlot, $scheduleMap);

        return $this->calculateStagesWithReferenceStage($all->toArray()[$referenceStageIndex], $referenceStageProposedTimeSlot, $scheduleMap);
    }

    /**
     * @param GenericList<ParallelStages> $before
     * @param Map<string, TimeSlot>       $scheduleMap
     *
     * @return Map<string, TimeSlot>
     */
    private function calculateStagesBeforeCritical(
        GenericList $before,
        TimeSlot $stageProposedTimeSlot,
        Map $scheduleMap
    ): Map {
        $currentStart = $stageProposedTimeSlot->from;
        foreach ($before->toArray() as $currentStages) {
            $stageDuration = $currentStages->duration();
            $start = $currentStart->sub($stageDuration->toDateInterval());
            foreach ($currentStages->stages()->toArray() as $stage) {
                $scheduleMap = $scheduleMap->put($stage->name(), new TimeSlot($start, $start->add($stage->duration()->toDateInterval())));
            }
        }

        return $scheduleMap;
    }

    /**
     * @param GenericList<ParallelStages> $after
     * @param Map<string, TimeSlot>       $scheduleMap
     *
     * @return Map<string, TimeSlot>
     */
    private function calculateStagesAfterCritical(
        GenericList $after,
        TimeSlot $stageProposedTimeSlot,
        Map $scheduleMap
    ): Map {
        $currentStart = $stageProposedTimeSlot->to;
        foreach ($after->toArray() as $currentStages) {
            foreach ($currentStages->stages()->toArray() as $stage) {
                $scheduleMap = $scheduleMap->put($stage->name(), new TimeSlot($currentStart, $currentStart->add($stage->duration()->toDateInterval())));
            }
            $currentStart = $currentStart->add($currentStages->duration()->toDateInterval());
        }

        return $scheduleMap;
    }

    /**
     * @param Map<string, TimeSlot> $scheduleMap
     *
     * @return Map<string, TimeSlot>
     */
    private function calculateStagesWithReferenceStage(
        ParallelStages $stagesWithReference,
        TimeSlot $stageProposedTimeSlot,
        Map $scheduleMap
    ): Map {
        $currentStart = $stageProposedTimeSlot->from;
        foreach ($stagesWithReference->stages()->toArray() as $stage) {
            $scheduleMap = $scheduleMap->put($stage->name(), new TimeSlot($currentStart, $currentStart->add($stage->duration()->toDateInterval())));
        }

        return $scheduleMap;
    }

    /**
     * @param GenericList<ParallelStages> $all
     */
    private function findReferenceStageIndex(Stage $referenceStage, GenericList $all): int
    {
        $stagesWithTheReferenceStageWithProposedTimeIndex = -1;
        foreach ($all->toArray() as $index => $stages) {
            if ($stages->stages()->map(fn (Stage $s) => $s->name())->contains($referenceStage->name())) {
                $stagesWithTheReferenceStageWithProposedTimeIndex = $index;
                break;
            }
        }

        return $stagesWithTheReferenceStageWithProposedTimeIndex;
    }
}
