<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\Calendars;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\Schedule\Schedule;
use DomainDrivers\SmartSchedule\Shared\EventsPublisher;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use Munus\Collection\Stream;
use Munus\Collection\Stream\Collectors;
use Symfony\Component\Clock\ClockInterface;

final readonly class PlanChosenResources
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private AvailabilityFacade $availabilityFacade,
        private EventsPublisher $eventsPublisher,
        private ClockInterface $clock
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
        $this->eventsPublisher->publish(NeededResourcesChosen::new($projectId, $chosenResources, $timeBoundaries, $this->clock->now()));
    }

    public function adjustStagesToResourceAvailability(ProjectId $projectId, TimeSlot $timeBoundaries, Stage ...$stages): void
    {
        $neededResources = $this->neededResources($stages);
        $project = $this->projectRepository->getById($projectId);
        $this->defineResourcesWithinDates($projectId, $neededResources, $timeBoundaries);
        $neededResourcesCalendars = $this->availabilityFacade->loadCalendars($neededResources, $timeBoundaries);
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
