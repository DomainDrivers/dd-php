<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;

interface ResourceAvailabilityReadModel
{
    public function load(ResourceId $resourceId, TimeSlot $timeSlot): Calendar;

    /**
     * @param Set<ResourceId> $resourceIds
     */
    public function loadAll(Set $resourceIds, TimeSlot $timeSlot): Calendars;
}
