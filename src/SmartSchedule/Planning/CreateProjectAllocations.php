<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

final readonly class CreateProjectAllocations
{
    public function __construct(
        private ProjectRepository $projectRepository
    ) {
    }

    // can react to ScheduleCalculated event
    public function createProjectAllocations(ProjectId $projectId): void
    {
        $project = $this->projectRepository->getById($projectId);
        $schedule = $project->schedule();
        // for each stage in schedule
        //      create allocation
        //      allocate chosen resources (or find equivalents)
        //      start risk analysis
    }
}
