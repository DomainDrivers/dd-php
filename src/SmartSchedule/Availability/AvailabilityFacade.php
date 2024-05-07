<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability;

final class AvailabilityFacade
{
    public function availabilitiesOfResources(): Calendars
    {
        return Calendars::of();
    }
}
