<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use Munus\Collection\Stream\Collectors;

final readonly class CapabilityScheduler
{
    public function __construct(
        private AvailabilityFacade $availabilityFacade,
        private AllocatableCapabilityRepository $allocatableCapabilityRepository
    ) {
    }

    /**
     * @param GenericList<CapabilitySelector> $capabilities
     *
     * @return GenericList<AllocatableCapabilityId>
     */
    public function scheduleResourceCapabilitiesForPeriod(AllocatableResourceId $resourceId, GenericList $capabilities, TimeSlot $timeSlot): GenericList
    {
        $allocatableResourceIds = $this->createAllocatableResources($resourceId, $capabilities, $timeSlot);
        $allocatableResourceIds->forEach(fn (AllocatableCapabilityId $id) => $this->availabilityFacade->createResourceSlots($id->toAvailabilityResourceId(), $timeSlot));

        return $allocatableResourceIds;
    }

    /**
     * @param Set<AllocatableResourceId> $resources
     *
     * @return GenericList<AllocatableCapabilityId>
     */
    public function scheduleMultipleResourcesForPeriod(Set $resources, Capability $capability, TimeSlot $timeSlot): GenericList
    {
        /** @var GenericList<AllocatableCapability> $allocatableCapability */
        $allocatableCapability = $resources->toStream()->map(fn (AllocatableResourceId $id): AllocatableCapability => new AllocatableCapability($id, CapabilitySelector::canJustPerform($capability), $timeSlot))->collect(Collectors::toList());
        $this->allocatableCapabilityRepository->saveAll($allocatableCapability);
        $allocatableCapability->forEach(fn (AllocatableCapability $a) => $this->availabilityFacade->createResourceSlots($a->id()->toAvailabilityResourceId(), $timeSlot));

        return $allocatableCapability->map(fn (AllocatableCapability $a): AllocatableCapabilityId => $a->id());
    }

    public function findResourceCapabilities(AllocatableResourceId $resourceId, Capability $capability, TimeSlot $period): ?AllocatableCapabilityId
    {
        return $this->allocatableCapabilityRepository->findByResourceIdAndCapabilityAndTimeSlot($resourceId, $capability, $period)
            ->map(fn (AllocatableCapability $a) => $a->id())
            ->findFirst()
            ->getOrNull();
    }

    /**
     * @param Set<Capability> $capabilities
     */
    public function findResourceCapabilitiesFromSet(AllocatableResourceId $resourceId, Set $capabilities, TimeSlot $timeSlot): ?AllocatableCapabilityId
    {
        return $this->allocatableCapabilityRepository->findByResourceIdAndTimeSlot($resourceId, $timeSlot)
            ->filter(fn (AllocatableCapability $a) => $a->canPerformAll($capabilities))
            ->map(fn (AllocatableCapability $a) => $a->id())
            ->findFirst()
            ->getOrNull();
    }

    /**
     * @param GenericList<CapabilitySelector> $capabilities
     *
     * @return GenericList<AllocatableCapabilityId>
     */
    private function createAllocatableResources(AllocatableResourceId $resourceId, GenericList $capabilities, TimeSlot $timeSlot): GenericList
    {
        $allocatableResources = $capabilities->map(fn (CapabilitySelector $c): AllocatableCapability => new AllocatableCapability($resourceId, $c, $timeSlot));
        $this->allocatableCapabilityRepository->saveAll($allocatableResources);

        return $allocatableResources->map(fn (AllocatableCapability $a): AllocatableCapabilityId => $a->id());
    }
}
