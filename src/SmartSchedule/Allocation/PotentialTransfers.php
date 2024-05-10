<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\Cashflow\Earnings;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\SmartSchedule\Simulation\Demand as SimulationDemand;
use DomainDrivers\SmartSchedule\Simulation\Demands as SimulationDemands;
use DomainDrivers\SmartSchedule\Simulation\ProjectId;
use DomainDrivers\SmartSchedule\Simulation\SimulatedProject;
use Munus\Collection\GenericList;
use Munus\Collection\Map;
use Munus\Collection\Stream\Collectors;

final readonly class PotentialTransfers
{
    /**
     * @param Map<string, Earnings> $earnings
     */
    public function __construct(public ProjectsAllocationsSummary $summary, public Map $earnings)
    {
    }

    public function transfer(
        ProjectAllocationsId $projectFrom,
        ProjectAllocationsId $projectTo,
        AllocatedCapability $capability,
        TimeSlot $forSlot): self
    {
        $from = $this->summary->projectAllocations->get($projectFrom->toString());
        $to = $this->summary->projectAllocations->get($projectTo->toString());
        if ($from->isEmpty() || $to->isEmpty()) {
            return $this;
        }
        $newAllocationsProjectFrom = $from->get()->remove($capability->allocatedCapabilityID, $forSlot);
        if ($from->equals($newAllocationsProjectFrom)) {
            return $this;
        }
        $newAllocations = $this->summary->projectAllocations->put($projectFrom->toString(), $newAllocationsProjectFrom);
        $newAllocationsProjectTo = $to->get()->add(AllocatedCapability::new($capability->resourceId, $capability->capability, $forSlot));
        $newAllocations = $newAllocations->put($projectTo->toString(), $newAllocationsProjectTo);

        return new self(new ProjectsAllocationsSummary($this->summary->timeSlots, $newAllocations, $this->summary->demands), $this->earnings);
    }

    /**
     * @return GenericList<SimulatedProject>
     */
    public function toSimulatedProjects(): GenericList
    {
        return $this->summary->projectAllocations->keys()->toStream()
            ->map(fn (string $projectId) => new SimulatedProject(
                ProjectId::fromString($projectId),
                fn () => $this->earnings->get($projectId)->get()->value,
                $this->getMissingDemands($projectId)))
            ->collect(Collectors::toList());
    }

    private function getMissingDemands(string $projectId): SimulationDemands
    {
        $allDemands = $this->summary->demands->get($projectId)->get()->missingDemands($this->summary->projectAllocations->get($projectId)->get());

        return new SimulationDemands($allDemands->all->map(fn (Demand $demand) => SimulationDemand::for($demand->capability, $demand->slot)));
    }
}
