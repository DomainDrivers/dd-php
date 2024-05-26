<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;

final readonly class Demand implements \Stringable
{
    public function __construct(public Capability $capability, public TimeSlot $slot)
    {
    }

    public function __toString(): string
    {
        return (string) \json_encode([
            'name' => $this->capability->name,
            'type' => $this->capability->type,
            'from' => $this->slot->from->format(DATE_RFC3339),
            'to' => $this->slot->to->format(DATE_RFC3339),
        ]);
    }
}
