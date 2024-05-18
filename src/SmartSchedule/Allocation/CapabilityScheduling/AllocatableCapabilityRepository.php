<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;

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
     * @return GenericList<AllocatableCapability>
     */
    public function findByCapabilityWithin(Capability $capability, TimeSlot $timeSlot): GenericList;
}
