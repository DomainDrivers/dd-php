<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use Decimal\Decimal;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\SmartSchedule\Simulation\SimulatedCapabilities;
use DomainDrivers\SmartSchedule\Simulation\SimulationFacade;
use Symfony\Component\Uid\Uuid;

final readonly class AllocationFacade
{
    public function __construct(private SimulationFacade $simulationFacade)
    {
    }

    public function checkPotentialTransfer(
        Projects $projects,
        Uuid $projectFrom,
        Uuid $projectTo,
        AllocatedCapability $capability,
        TimeSlot $forSlot
    ): Decimal {
        $resultBefore = $this->simulationFacade->whatIsTheOptimalSetup($projects->toSimulatedProjects(), SimulatedCapabilities::none());
        $projects = $projects->transfer($projectFrom, $projectTo, $capability, $forSlot);
        $resultAfter = $this->simulationFacade->whatIsTheOptimalSetup($projects->toSimulatedProjects(), SimulatedCapabilities::none());

        return $resultAfter->profit->sub($resultBefore->profit);
    }
}
