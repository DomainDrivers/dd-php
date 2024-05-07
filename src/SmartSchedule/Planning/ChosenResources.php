<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Shared\ResourceName;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;

final readonly class ChosenResources
{
    /**
     * @param Set<ResourceName> $resources
     */
    public function __construct(public Set $resources, public TimeSlot $timeSlot)
    {
    }

    public static function none(): self
    {
        return new self(Set::empty(), TimeSlot::empty());
    }
}
