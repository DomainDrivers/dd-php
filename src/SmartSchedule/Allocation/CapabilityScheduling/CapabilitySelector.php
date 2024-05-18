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
    public function __construct(public Set $capabilities, public SelectingPolicy $selectingPolicy)
    {
    }

    /**
     * @param Set<Capability> $capabilities
     */
    public static function canPerformOneOf(Set $capabilities): self
    {
        return new self($capabilities, SelectingPolicy::ONE_OF_ALL);
    }

    /**
     * @param Set<Capability> $capabilities
     */
    public static function canPerformAllAtTheTime(Set $capabilities): self
    {
        return new self($capabilities, SelectingPolicy::ALL_SIMULTANEOUSLY);
    }

    public function canPerform(Capability $capability): bool
    {
        return $this->capabilities->contains($capability);
    }

    /**
     * @param Set<Capability> $capabilities
     */
    public function canPerformAll(Set $capabilities): bool
    {
        if ($capabilities->length() === 1) {
            return $this->canPerform($capabilities->get());
        }

        return $this->selectingPolicy === SelectingPolicy::ALL_SIMULTANEOUSLY && $this->capabilities->containsAll($capabilities);
    }
}
