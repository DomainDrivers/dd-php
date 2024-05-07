<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStagesList;
use DomainDrivers\SmartSchedule\Planning\Schedule\Schedule;

final readonly class ProjectCard
{
    public function __construct(
        public ProjectId $projectId,
        public string $name,
        public ParallelStagesList $parallelStagesList,
        public Demands $demands,
        public Schedule $schedule,
        public DemandsPerStage $demandsPerStage,
        public ChosenResources $chosenResources
    ) {
    }
}
