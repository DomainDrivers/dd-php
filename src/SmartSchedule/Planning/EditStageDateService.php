<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;

final readonly class EditStageDateService
{
    public function __construct(
        private ProjectRepository $projectRepository
    ) {
    }

    public function editStageDate(ProjectId $projectId, Stage $stage, TimeSlot $newSlot): void
    {
        $project = $this->projectRepository->getById($projectId);
        $schedule = $project->schedule();
        // redefine schedule
        // for each stage in schedule
        //      recreate allocation
        //      reallocate chosen resources (or find equivalents)
        //      start risk analysis
    }
}
