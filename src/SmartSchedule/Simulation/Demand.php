<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

final readonly class Demand
{
    private function __construct(public Capability $capability, public TimeSlot $timeSlot)
    {
    }

    public static function for(Capability $capability, TimeSlot $timeSlot): self
    {
        return new self($capability, $timeSlot);
    }

    public function isSatisfiedBy(AvailableResourceCapability $availableResourceCapability): bool
    {
        return $availableResourceCapability->performs($this->capability)
            && $this->timeSlot->within($availableResourceCapability->timeSlot);
    }
}
