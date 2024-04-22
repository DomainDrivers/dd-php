<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use Decimal\Decimal;

final readonly class SimulatedProject
{
    public function __construct(
        public ProjectId $projectId,
        public Decimal $earnings,
        public Demands $missingDemands
    ) {
    }

    public function allDemandsSatisfied(): bool
    {
        return $this->missingDemands->all->isEmpty();
    }
}
