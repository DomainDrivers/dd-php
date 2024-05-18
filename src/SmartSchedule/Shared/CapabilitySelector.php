<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\Set;
use Munus\Value\Comparable;

final readonly class CapabilitySelector implements Comparable
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

    public static function canJustPerform(Capability $capability): self
    {
        return new self(Set::of($capability), SelectingPolicy::ONE_OF_ALL);
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

    #[\Override]
    public function equals(Comparable $other): bool
    {
        return self::class === $other::class && $this->selectingPolicy === $other->selectingPolicy && $this->capabilities->equals($other->capabilities);
    }
}
