<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Schedule;

use DomainDrivers\SmartSchedule\Availability\Calendars;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Shared\ResourceName;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Map;
use Munus\Collection\Stream\Collectors;

final readonly class ScheduleBasedOnChosenResourcesAvailabilityCalculator
{
    /**
     * @param GenericList<Stage> $stages
     *
     * @return Map<string, TimeSlot>
     */
    public function calculate(Calendars $chosenResourcesCalendars, GenericList $stages): Map
    {
        $schedule = Map::empty();
        foreach ($stages->toArray() as $stage) {
            $proposedSlot = $this->findSlotForStage($chosenResourcesCalendars, $stage);
            if ($proposedSlot->isEmpty()) {
                return Map::empty();
            }

            $schedule = $schedule->put($stage->name(), $proposedSlot);
        }

        return $schedule;
    }

    private function findSlotForStage(Calendars $chosenResourcesCalendars, Stage $stage): TimeSlot
    {
        $foundSlots = $this->possibleSlots($chosenResourcesCalendars, $stage);
        if ($foundSlots->contains(TimeSlot::empty())) {
            return TimeSlot::empty();
        }
        $commonSlotForAllResources = $this->findCommonPartOfSlots($foundSlots);
        while (!$this->isSlotLongEnoughForStage($stage, $commonSlotForAllResources)) {
            $commonSlotForAllResources = $commonSlotForAllResources->stretch(Duration::ofDays(1));
        }

        return new TimeSlot($commonSlotForAllResources->from, $commonSlotForAllResources->from->modify(sprintf('+%s seconds', $stage->duration()->seconds)));
    }

    private function isSlotLongEnoughForStage(Stage $stage, TimeSlot $slot): bool
    {
        return $slot->duration()->seconds >= $stage->duration()->seconds;
    }

    /**
     * @param GenericList<TimeSlot> $foundSlots
     */
    private function findCommonPartOfSlots(GenericList $foundSlots): TimeSlot
    {
        if ($foundSlots->isEmpty()) {
            return TimeSlot::empty();
        }

        return $foundSlots->reduce(fn (TimeSlot $acc, TimeSlot $next) => $acc->commonPartWith($next));
    }

    /**
     * @return GenericList<TimeSlot>
     */
    private function possibleSlots(Calendars $chosenResourcesCalendars, Stage $stage): GenericList
    {
        return $stage->resources()->toStream()->map(
            fn (ResourceName $resource) => $chosenResourcesCalendars
                ->get($resource)
                ->availableSlots()
                ->filter(fn (TimeSlot $slot) => $this->isSlotLongEnoughForStage($stage, $slot))
                ->getOrElse(TimeSlot::empty())
        )->collect(Collectors::toList());
    }
}
