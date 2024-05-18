<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityFinder;
use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\Owner;
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
        private AvailabilityFacade $availabilityFacade,
        private CapabilityFinder $capabilityFinder,
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
    public function allocateToProject(ProjectAllocationsId $projectId, AllocatableCapabilityId $allocatableCapabilityId, Capability $capability, TimeSlot $timeSlot): Option
    {
        // yes, one transaction crossing 2 modules.
        if (!$this->capabilityFinder->isPresent($allocatableCapabilityId)) {
            return $this->empty();
        }
        if (!$this->availabilityFacade->block($allocatableCapabilityId->toAvailabilityResourceId(), $timeSlot, Owner::of($projectId->id))) {
            return $this->empty();
        }
        $allocations = $this->projectAllocationsRepository->getById($projectId);
        $event = $allocations->allocate($allocatableCapabilityId, $capability, $timeSlot, $this->clock->now());
        $this->projectAllocationsRepository->save($allocations);

        return $event->map(fn (CapabilitiesAllocated $c) => $c->allocatedCapabilityId);
    }

    public function releaseFromProject(ProjectAllocationsId $projectId, AllocatableCapabilityId $allocatableCapabilityId, TimeSlot $timeSlot): bool
    {
        // can release not scheduled capability - at least for now. Hence no check to capabilityFinder
        $this->availabilityFacade->release($allocatableCapabilityId->toAvailabilityResourceId(), $timeSlot, Owner::of($projectId->id));
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

    /**
     * @return Option<Uuid>
     */
    private function empty(): Option
    {
        /** @var Option<Uuid> $option */
        $option = Option::none();

        return $option;
    }
}
