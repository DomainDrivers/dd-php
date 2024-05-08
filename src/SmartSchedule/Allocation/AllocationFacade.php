<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;
use Munus\Control\Option;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

final readonly class AllocationFacade
{
    public function __construct(
        private ProjectAllocationsRepository $projectAllocationsRepository,
        private ClockInterface $clock
    ) {
    }

    public function createAllocation(TimeSlot $timeSlot, Demands $scheduledDemands): ProjectAllocationsId
    {
        $projectId = ProjectAllocationsId::newOne();
        $this->projectAllocationsRepository->save(new ProjectAllocations($projectId, Allocations::none(), $scheduledDemands, $timeSlot));

        return $projectId;
    }

    /**
     * @param Set<ProjectAllocationsId> $projectIds
     */
    public function findAllProjectsAllocationsBy(Set $projectIds): ProjectsAllocationsSummary
    {
        return ProjectsAllocationsSummary::of($this->projectAllocationsRepository->findAllById($projectIds));
    }

    public function findAllProjectsAllocations(): ProjectsAllocationsSummary
    {
        return ProjectsAllocationsSummary::of($this->projectAllocationsRepository->findAll());
    }

    /**
     * @return Option<Uuid>
     */
    public function allocateToProject(ProjectAllocationsId $projectId, ResourceId $resourceId, Capability $capability, TimeSlot $timeSlot): Option
    {
        $allocations = $this->projectAllocationsRepository->getById($projectId);
        $event = $allocations->allocate($resourceId, $capability, $timeSlot, $this->clock->now());
        $this->projectAllocationsRepository->save($allocations);

        return $event->map(fn (CapabilitiesAllocated $c) => $c->allocatedCapabilityId);
    }

    public function releaseFromProject(ProjectAllocationsId $projectId, Uuid $allocatableCapabilityId, TimeSlot $timeSlot): bool
    {
        $allocations = $this->projectAllocationsRepository->getById($projectId);
        $event = $allocations->release($allocatableCapabilityId, $timeSlot, $this->clock->now());
        $this->projectAllocationsRepository->save($allocations);

        return $event->isPresent();
    }

    public function editProjectDates(ProjectAllocationsId $projectId, TimeSlot $fromTo): void
    {
        $allocations = $this->projectAllocationsRepository->getById($projectId);
        $allocations->defineSlot($fromTo, $this->clock->now());
        $this->projectAllocationsRepository->save($allocations);
    }

    public function scheduleProjectAllocationDemands(ProjectAllocationsId $projectId, Demands $demands): void
    {
        $allocations = $this->projectAllocationsRepository->findById($projectId)->getOrElse(ProjectAllocations::empty($projectId));
        $allocations->addDemands($demands, $this->clock->now());
        $this->projectAllocationsRepository->save($allocations);
    }
}
