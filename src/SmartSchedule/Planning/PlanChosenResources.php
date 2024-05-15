<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Availability\Calendars;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\Schedule\Schedule;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use Munus\Collection\Stream;
use Munus\Collection\Stream\Collectors;

final readonly class PlanChosenResources
{
    public function __construct(
        private ProjectRepository $projectRepository
    ) {
    }

    /**
     * @param Set<ResourceId> $chosenResources
     */
    public function defineResourcesWithinDates(ProjectId $projectId, Set $chosenResources, TimeSlot $timeBoundaries): void
    {
        $project = $this->projectRepository->getById($projectId);
        $project->addChosenResources(new ChosenResources($chosenResources, $timeBoundaries));
        $this->projectRepository->save($project);
    }

    public function adjustStagesToResourceAvailability(ProjectId $projectId, TimeSlot $timeBoundaries, Stage ...$stages): void
    {
        $neededResources = $this->neededResources($stages);
        $project = $this->projectRepository->getById($projectId);
        $this->defineResourcesWithinDates($projectId, $neededResources, $timeBoundaries);
        // TODO when availability is implemented
        $neededResourcesCalendars = Calendars::of();
        $project->addSchedule($this->createScheduleAdjustingToCalendars($neededResourcesCalendars, GenericList::ofAll($stages)));
        $this->projectRepository->save($project);
    }

    /**
     * @param GenericList<Stage> $stages
     */
    private function createScheduleAdjustingToCalendars(Calendars $neededResourcesCalendars, GenericList $stages): Schedule
    {
        return Schedule::basedOnChosenResourcesAvailability($neededResourcesCalendars, $stages);
    }

    /**
     * @param array<Stage> $stages
     *
     * @return Set<ResourceId>
     */
    private function neededResources(array $stages): Set
    {
        return Stream::ofAll($stages)
            ->flatMap(fn (Stage $s) => $s->resources())
            ->collect(Collectors::toSet());
    }
}
