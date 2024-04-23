<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use Decimal\Decimal;

final readonly class SimulatedProject
{
    /**
     * @param \Closure(): Decimal $value
     */
    public function __construct(
        public ProjectId $projectId,
        public \Closure $value,
        public Demands $missingDemands
    ) {
    }

    public function calculateValue(): Decimal
    {
        return ($this->value)();
    }
}
