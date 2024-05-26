<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use Decimal\Decimal;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilitySummary;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\CashFlowFacade;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\SmartSchedule\Simulation\SimulatedCapabilities;
use DomainDrivers\SmartSchedule\Simulation\SimulationFacade;

final readonly class PotentialTransfersService
{
    public function __construct(
        private SimulationFacade $simulationFacade,
        private ProjectAllocationsRepository $projectAllocationsRepository,
        private CashFlowFacade $cashFlowFacade
    ) {
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

    public function profitAfterMovingCapabilities(
        ProjectAllocationsId $projectId,
        AllocatableCapabilitySummary $capabilityToMove,
        TimeSlot $timeSlot
    ): Decimal {
        // cached?
        $potentialTransfers = new PotentialTransfers(ProjectsAllocationsSummary::of($this->projectAllocationsRepository->findAll()), $this->cashFlowFacade->findAllEarnings());

        return $this->checkPotentialTransferTo($potentialTransfers, $projectId, $capabilityToMove, $timeSlot);
    }

    private function checkPotentialTransferTo(
        PotentialTransfers $transfers,
        ProjectAllocationsId $projectTo,
        AllocatableCapabilitySummary $capabilityToMove,
        TimeSlot $forSlot
    ): Decimal {
        $resultBefore = $this->simulationFacade->whatIsTheOptimalSetup($transfers->toSimulatedProjects(), SimulatedCapabilities::none());
        $transfers = $transfers->transferTo($projectTo, $capabilityToMove, $forSlot);
        $resultAfter = $this->simulationFacade->whatIsTheOptimalSetup($transfers->toSimulatedProjects(), SimulatedCapabilities::none());

        return $resultAfter->profit->sub($resultBefore->profit);
    }
}
