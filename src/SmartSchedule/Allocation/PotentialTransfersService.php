<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use Decimal\Decimal;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\SmartSchedule\Simulation\SimulatedCapabilities;
use DomainDrivers\SmartSchedule\Simulation\SimulationFacade;

final readonly class PotentialTransfersService
{
    public function __construct(private SimulationFacade $simulationFacade)
    {
    }

    public function checkPotentialTransfer(
        PotentialTransfers $transfers,
        ProjectAllocationsId $projectFrom,
        ProjectAllocationsId $projectTo,
        AllocatedCapability $capability,
        TimeSlot $forSlot
    ): Decimal {
        $resultBefore = $this->simulationFacade->whatIsTheOptimalSetup($transfers->toSimulatedProjects(), SimulatedCapabilities::none());
        $transfer = $transfers->transfer($projectFrom, $projectTo, $capability, $forSlot);
        $resultAfter = $this->simulationFacade->whatIsTheOptimalSetup($transfer->toSimulatedProjects(), SimulatedCapabilities::none());

        return $resultAfter->profit->sub($resultBefore->profit);
    }
}
