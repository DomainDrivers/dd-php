<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Set;

interface ResourceAvailabilityRepository
{
    public function saveNew(ResourceAvailability $resourceAvailability): void;

    public function saveGroup(ResourceGroupedAvailability $groupedAvailability): void;

    /**
     * @return GenericList<ResourceAvailability>
     */
    public function loadAllWithinSlot(ResourceAvailabilityId $resourceId, TimeSlot $segment): GenericList;

    /**
     * @return GenericList<ResourceAvailability>
     */
    public function loadAllByParentIdWithinSlot(ResourceAvailabilityId $parentId, TimeSlot $segment): GenericList;

    public function saveCheckingVersion(ResourceAvailability $resourceAvailability): bool;

    public function saveCheckingVersions(ResourceGroupedAvailability $groupedAvailability): bool;

    public function loadById(ResourceAvailabilityId $availabilityId): ?ResourceAvailability;

    /**
     * @param Set<ResourceAvailabilityId> $resourcesId
     */
    public function loadAvailabilitiesOfRandomResourceWithin(Set $resourcesId, TimeSlot $normalized): ResourceGroupedAvailability;
}
