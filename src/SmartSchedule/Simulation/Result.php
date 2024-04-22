<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use Decimal\Decimal;
use Munus\Collection\GenericList;
use Munus\Collection\Map;
use Munus\Collection\Set;

final readonly class Result
{
    /**
     * @param GenericList<SimulatedProject>                 $chosenProjects
     * @param Map<string, Set<AvailableResourceCapability>> $resourcesAllocatedToProjects
     */
    public function __construct(
        public Decimal $profit,
        public GenericList $chosenProjects,
        public Map $resourcesAllocatedToProjects
    ) {
    }
}
