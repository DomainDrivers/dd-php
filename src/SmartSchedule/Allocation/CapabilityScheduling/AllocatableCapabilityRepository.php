<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Control\Option;

interface AllocatableCapabilityRepository
{
    /**
     * @param GenericList<AllocatableCapability> $all
     */
    public function saveAll(GenericList $all): void;

    /**
     * @param GenericList<AllocatableCapabilityId> $ids
     *
     * @return GenericList<AllocatableCapability>
     */
    public function findAllById(GenericList $ids): GenericList;

    /**
     * @return Option<AllocatableCapability>
     */
    public function findById(AllocatableCapabilityId $id): Option;

    public function existsById(AllocatableCapabilityId $allocatableCapabilityId): bool;

    /**
     * @return GenericList<AllocatableCapability>
     */
    public function findByCapabilityWithin(Capability $capability, TimeSlot $timeSlot): GenericList;

    /**
     * @return GenericList<AllocatableCapability>
     */
    public function findByResourceIdAndCapabilityAndTimeSlot(AllocatableResourceId $allocatableResourceId, Capability $capability, TimeSlot $timeSlot): GenericList;

    /**
     * @return GenericList<AllocatableCapability>
     */
    public function findByResourceIdAndTimeSlot(AllocatableResourceId $allocatableResourceId, TimeSlot $timeSlot): GenericList;
}
