<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\Set;

final readonly class CapabilitySelector
{
    /**
     * @param Set<Capability> $capabilities
     */
    public static function canPerformOneOf(Set $capabilities): self
    {
        return new self();
    }

    /**
     * @param Set<Capability> $beingAnAdmin
     */
    public static function canPerformAllAtTheTime(Set $beingAnAdmin): self
    {
        return new self();
    }

    public function canPerform(Capability $capability): bool
    {
        return false;
    }

    /**
     * @param Set<Capability> $capabilities
     */
    public function canPerformAll(Set $capabilities): bool
    {
        return false;
    }
}
