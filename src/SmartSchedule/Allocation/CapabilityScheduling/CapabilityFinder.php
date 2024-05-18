<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Stream\Collectors;

final readonly class CapabilityFinder
{
    public function __construct(
        private AvailabilityFacade $availabilityFacade,
        private AllocatableCapabilityRepository $allocatableCapabilityRepository
    ) {
    }

    public function findAvailableCapabilities(Capability $capability, TimeSlot $timeSlot): AllocatableCapabilitiesSummary
    {
        return $this->createSummary($this->filterAvailabilityInTimeSlot(
            $this->allocatableCapabilityRepository->findByCapabilityWithin($capability, $timeSlot),
            $timeSlot
        ));
    }

    public function findCapabilities(Capability $capability, TimeSlot $timeSlot): AllocatableCapabilitiesSummary
    {
        return $this->createSummary($this->allocatableCapabilityRepository->findByCapabilityWithin($capability, $timeSlot));
    }

    /**
     * @param GenericList<AllocatableCapabilityId> $allocatableCapabilityIds
     */
    public function findById(GenericList $allocatableCapabilityIds): AllocatableCapabilitiesSummary
    {
        return $this->createSummary($this->allocatableCapabilityRepository->findAllById($allocatableCapabilityIds));
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
    private function createSummary(GenericList $from): AllocatableCapabilitiesSummary
    {
        return new AllocatableCapabilitiesSummary($from->map(fn (AllocatableCapability $a) => new AllocatableCapabilitySummary(
            $a->id(),
            $a->resourceId(),
            $a->capability(),
            $a->timeSlot()
        )));
    }
}
