<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Stream\Collectors;

class CapabilityFinder
{
    public function __construct(
        private AvailabilityFacade $availabilityFacade,
        private AllocatableCapabilityRepository $allocatableCapabilityRepository
    ) {
    }

    public function findAvailableCapabilities(Capability $capability, TimeSlot $timeSlot): AllocatableCapabilitiesSummary
    {
        return $this->createCapabilitiesSummary($this->filterAvailabilityInTimeSlot(
            $this->allocatableCapabilityRepository->findByCapabilityWithin($capability, $timeSlot),
            $timeSlot
        ));
    }

    public function findCapabilities(Capability $capability, TimeSlot $timeSlot): AllocatableCapabilitiesSummary
    {
        return $this->createCapabilitiesSummary($this->allocatableCapabilityRepository->findByCapabilityWithin($capability, $timeSlot));
    }

    /**
     * @param GenericList<AllocatableCapabilityId> $allocatableCapabilityIds
     */
    public function findById(GenericList $allocatableCapabilityIds): AllocatableCapabilitiesSummary
    {
        return $this->createCapabilitiesSummary($this->allocatableCapabilityRepository->findAllById($allocatableCapabilityIds));
    }

    public function findOneById(AllocatableCapabilityId $allocatableCapabilityId): ?AllocatableCapabilitySummary
    {
        return $this->allocatableCapabilityRepository->findById($allocatableCapabilityId)->map($this->createSummary(...))->getOrNull();
    }

    public function isPresent(AllocatableCapabilityId $allocatableCapabilityId): bool
    {
        return $this->allocatableCapabilityRepository->existsById($allocatableCapabilityId);
    }

    /**
     * @param GenericList<AllocatableCapability> $findAllocatableCapability
     *
     * @return GenericList<AllocatableCapability>
     */
    private function filterAvailabilityInTimeSlot(GenericList $findAllocatableCapability, TimeSlot $timeSlot): GenericList
    {
        $resourceIds = $findAllocatableCapability->toStream()->map(fn (AllocatableCapability $a) => $a->id()->toAvailabilityResourceId())->collect(Collectors::toSet());
        $calendars = $this->availabilityFacade->loadCalendars($resourceIds, $timeSlot);

        return $findAllocatableCapability->filter(fn (AllocatableCapability $a): bool => $calendars->get($a->id()->toAvailabilityResourceId())->availableSlots()->contains($timeSlot));
    }

    /**
     * @param GenericList<AllocatableCapability> $from
     */
    private function createCapabilitiesSummary(GenericList $from): AllocatableCapabilitiesSummary
    {
        return new AllocatableCapabilitiesSummary($from->map($this->createSummary(...)));
    }

    private function createSummary(AllocatableCapability $capability): AllocatableCapabilitySummary
    {
        return new AllocatableCapabilitySummary(
            $capability->id(),
            $capability->resourceId(),
            $capability->capabilities(),
            $capability->timeSlot()
        );
    }
}
