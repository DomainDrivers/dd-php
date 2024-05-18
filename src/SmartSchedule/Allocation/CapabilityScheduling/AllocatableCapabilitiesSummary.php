<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

use Munus\Collection\GenericList;

final readonly class AllocatableCapabilitiesSummary
{
    /**
     * @param GenericList<AllocatableCapabilitySummary> $all
     */
    public function __construct(public GenericList $all)
    {
    }
}
