<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStagesList;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\Parallelization\StageParallelization;
use DomainDrivers\SmartSchedule\Planning\Schedule\Schedule;
use DomainDrivers\SmartSchedule\Shared\EventsPublisher;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use Symfony\Component\Clock\ClockInterface;

final readonly class PlanningFacade
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private StageParallelization $stageParallelization,
        private PlanChosenResources $planChosenResources,
        private EventsPublisher $eventsPublisher,
        private ClockInterface $clock
    ) {
    }

    public function addNewProjectWith(string $name, Stage ...$stages): ProjectId
    {
        return $this->addNewProject($name, $this->stageParallelization->of(Set::ofAll($stages)));
    }

    public function addNewProject(string $name, ParallelStagesList $parallelizedStages): ProjectId
    {
        $project = new Project($name, $parallelizedStages);
        $this->projectRepository->save($project);

        return $project->id();
    }

    public function defineStartDate(ProjectId $projectId, \DateTimeImmutable $possibleStartDate): void
    {
        $project = $this->projectRepository->getById($projectId);
        $project->addScheduleOnStartDate($possibleStartDate);
        $this->projectRepository->save($project);
    }

    public function defineProjectStages(ProjectId $projectId, Stage ...$stages): void
    {
        $project = $this->projectRepository->getById($projectId);
        $project->defineStages($this->stageParallelization->of(Set::ofAll($stages)));
        $this->projectRepository->save($project);
    }

    public function addDemands(ProjectId $projectId, Demands $demands): void
    {
        $project = $this->projectRepository->getById($projectId);
        $project->addDemands($demands);
        $this->projectRepository->save($project);
        $this->eventsPublisher->publish(CapabilitiesDemanded::new($projectId, $project->demands(), $this->clock->now()));
    }

    public function defineDemandsPerStage(ProjectId $projectId, DemandsPerStage $demandsPerStage): void
    {
        $project = $this->projectRepository->getById($projectId);
        $project->addDemandsPerStage($demandsPerStage);
        $this->projectRepository->save($project);
        $this->eventsPublisher->publish(CapabilitiesDemanded::new($projectId, $project->demands(), $this->clock->now()));
    }

    /**
     * @param Set<ResourceId> $chosenResources
     */
    public function defineResourcesWithinDates(ProjectId $projectId, Set $chosenResources, TimeSlot $timeBoundaries): void
    {
        $this->planChosenResources->defineResourcesWithinDates($projectId, $chosenResources, $timeBoundaries);
    }

    public function adjustStagesToResourceAvailability(ProjectId $projectId, TimeSlot $timeBoundaries, Stage ...$stages): void
    {
        $this->planChosenResources->adjustStagesToResourceAvailability($projectId, $timeBoundaries, ...$stages);
    }

    public function planCriticalStageWithResource(ProjectId $projectId, Stage $criticalStage, ResourceId $resourceId, TimeSlot $stageTimeSlot): void
    {
        $project = $this->projectRepository->getById($projectId);
        $project->addScheduleOnStageTimeSlot($criticalStage, $stageTimeSlot);
        $this->projectRepository->save($project);
        $this->eventsPublisher->publish(CriticalStagePlanned::new($projectId, $stageTimeSlot, $resourceId, $this->clock->now()));
    }

    public function planCriticalStage(ProjectId $projectId, Stage $criticalStage, TimeSlot $stageTimeSlot): void
    {
        $project = $this->projectRepository->getById($projectId);
        $project->addScheduleOnStageTimeSlot($criticalStage, $stageTimeSlot);
        $this->projectRepository->save($project);
        $this->eventsPublisher->publish(CriticalStagePlanned::new($projectId, $stageTimeSlot, null, $this->clock->now()));
    }

    public function defineManualSchedule(ProjectId $projectId, Schedule $schedule): void
    {
        $project = $this->projectRepository->getById($projectId);
        $project->addSchedule($schedule);
        $this->projectRepository->save($project);
    }

    public function load(ProjectId $projectId): ProjectCard
    {
        return $this->toSummary($this->projectRepository->getById($projectId));
    }

    /**
     * @param Set<ProjectId> $projectsIds
     *
     * @return GenericList<ProjectCard>
     */
    public function loadAll(Set $projectsIds): GenericList
    {
        return $this->projectRepository->findAllById($projectsIds)->map($this->toSummary(...));
    }

    /**
     * @return GenericList<ProjectCard>
     */
    public function findAll(): GenericList
    {
        return $this->projectRepository->findAll()->map($this->toSummary(...));
    }

    private function toSummary(Project $project): ProjectCard
    {
        return new ProjectCard($project->id(), $project->name(), $project->parallelizedStages(), $project->demands(), $project->schedule(), $project->demandsPerStage(), $project->chosenResources());
    }
}
