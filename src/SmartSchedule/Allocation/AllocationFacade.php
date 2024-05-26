<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilitiesSummary;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilitySummary;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityFinder;
use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\EventsPublisher;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;
use Munus\Collection\Stream\Collectors;
use Munus\Control\Option;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

final readonly class AllocationFacade
{
    public function __construct(
        private ProjectAllocationsRepository $projectAllocationsRepository,
        private AvailabilityFacade $availabilityFacade,
        private CapabilityFinder $capabilityFinder,
        private EventsPublisher $eventsPublisher,
        private ClockInterface $clock
    ) {
    }

    public function createAllocation(TimeSlot $timeSlot, Demands $scheduledDemands): ProjectAllocationsId
    {
        $projectId = ProjectAllocationsId::newOne();
        $this->projectAllocationsRepository->save(new ProjectAllocations($projectId, Allocations::none(), $scheduledDemands, $timeSlot));
        $this->eventsPublisher->publish(ProjectAllocationScheduled::new($projectId, $timeSlot, $this->clock->now()));

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
    public function allocateToProject(ProjectAllocationsId $projectId, AllocatableCapabilityId $allocatableCapabilityId, TimeSlot $timeSlot): Option
    {
        // yes, one transaction crossing 2 modules.
        $capability = $this->capabilityFinder->findOneById($allocatableCapabilityId);
        if ($capability === null) {
            return $this->empty();
        }
        if (!$this->availabilityFacade->block($allocatableCapabilityId->toAvailabilityResourceId(), $timeSlot, Owner::of($projectId->id))) {
            return $this->empty();
        }

        return $this->allocate($projectId, $allocatableCapabilityId, $capability->capabilities, $timeSlot)
            ->map(fn (CapabilitiesAllocated $c) => $c->allocatedCapabilityId);
    }

    /**
     * @return Option<CapabilitiesAllocated>
     */
    private function allocate(ProjectAllocationsId $projectId, AllocatableCapabilityId $allocatableCapabilityId, CapabilitySelector $capability, TimeSlot $timeSlot): Option
    {
        $allocations = $this->projectAllocationsRepository->getById($projectId);
        $event = $allocations->allocate($allocatableCapabilityId, $capability, $timeSlot, $this->clock->now());
        $this->projectAllocationsRepository->save($allocations);

        return $event;
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
        $projectDatesSet = $allocations->defineSlot($fromTo, $this->clock->now());
        $this->projectAllocationsRepository->save($allocations);
        $projectDatesSet->ifPresent(fn (ProjectAllocationScheduled $event) => $this->eventsPublisher->publish($event));
    }

    public function scheduleProjectAllocationDemands(ProjectAllocationsId $projectId, Demands $demands): void
    {
        $allocations = $this->projectAllocationsRepository->findById($projectId)->getOrElse(ProjectAllocations::empty($projectId));
        $allocations->addDemands($demands, $this->clock->now());
        // event could be stored in a local store
        // always remember about transactional boundaries
        $this->projectAllocationsRepository->save($allocations);
    }

    public function allocateCapabilityToProjectForPeriod(ProjectAllocationsId $projectId, Capability $capability, TimeSlot $timeSlot): bool
    {
        $proposedCapabilities = $this->capabilityFinder->findCapabilities($capability, $timeSlot);
        if ($proposedCapabilities->all->isEmpty()) {
            return false;
        }
        $availabilityResourceIds = $proposedCapabilities->all->toStream()->map(fn (AllocatableCapabilitySummary $acs) => $acs->id->toAvailabilityResourceId())->collect(Collectors::toSet());
        $chosen = $this->availabilityFacade->blockRandomAvailable($availabilityResourceIds, $timeSlot, Owner::of($projectId->id));
        if ($chosen->isEmpty()) {
            return false;
        }
        $toAllocate = $this->findChosenAllocatableCapability($proposedCapabilities, $chosen->get());
        \assert($toAllocate !== null);

        return $this->allocate($projectId, $toAllocate->id, $toAllocate->capabilities, $timeSlot)->isPresent();
    }

    private function findChosenAllocatableCapability(AllocatableCapabilitiesSummary $proposedCapabilities, ResourceId $chosen): ?AllocatableCapabilitySummary
    {
        return $proposedCapabilities->all
            ->filter(fn (AllocatableCapabilitySummary $summary) => $summary->id->toAvailabilityResourceId()->getId()->equals($chosen->getId()))
            ->findFirst()
            ->getOrNull();
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
