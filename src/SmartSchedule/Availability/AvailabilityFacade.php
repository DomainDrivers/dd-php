<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;

final class AvailabilityFacade
{
    public function createResourceSlots(ResourceAvailabilityId $resourceId, TimeSlot $timeSlot): void
    {
    }

    public function block(ResourceAvailabilityId $resourceId, TimeSlot $timeSlot, Owner $requester): bool
    {
        return true;
    }

    public function release(ResourceAvailabilityId $resourceId, TimeSlot $timeSlot, Owner $requester): bool
    {
        return true;
    }

    public function disable(ResourceAvailabilityId $resourceId, TimeSlot $timeSlot, Owner $requester): bool
    {
        return true;
    }
}
