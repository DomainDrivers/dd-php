<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Availability\Segment\SegmentInMinutes;
use DomainDrivers\SmartSchedule\Availability\Segment\Segments;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use Munus\Collection\Stream\Collectors;
use Munus\Control\Option;

final readonly class ResourceGroupedAvailability
{
    /**
     * @param GenericList<ResourceAvailability> $resourceAvailabilities
     */
    public function __construct(public GenericList $resourceAvailabilities)
    {
    }

    public static function of(ResourceId $resourceId, TimeSlot $timeSlot): self
    {
        return new self(Segments::split($timeSlot, SegmentInMinutes::defaultSegment())
            ->map(fn (TimeSlot $segment) => ResourceAvailability::of(ResourceAvailabilityId::newOne(), $resourceId, $segment))
        );
    }

    public static function withParent(ResourceId $resourceId, TimeSlot $timeSlot, ResourceId $parentId): self
    {
        return new self(Segments::split($timeSlot, SegmentInMinutes::defaultSegment())
            ->map(fn (TimeSlot $segment) => ResourceAvailability::withParent(ResourceAvailabilityId::newOne(), $resourceId, $parentId, $segment))
        );
    }

    public function block(Owner $requester): bool
    {
        foreach ($this->resourceAvailabilities->toArray() as $resourceAvailability) {
            if (!$resourceAvailability->block($requester)) {
                return false;
            }
        }

        return true;
    }

    public function disable(Owner $requester): bool
    {
        foreach ($this->resourceAvailabilities->toArray() as $resourceAvailability) {
            if (!$resourceAvailability->disable($requester)) {
                return false;
            }
        }

        return true;
    }

    public function release(Owner $requester): bool
    {
        foreach ($this->resourceAvailabilities->toArray() as $resourceAvailability) {
            if (!$resourceAvailability->release($requester)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Set<Owner>
     */
    public function owners(): Set
    {
        return $this->resourceAvailabilities
            ->toStream()
            ->map(fn (ResourceAvailability $r) => $r->blockedBy())
            ->collect(Collectors::toSet());
    }

    public function size(): int
    {
        return $this->resourceAvailabilities->length();
    }

    /**
     * @return Option<ResourceId>
     */
    public function resourceId(): Option
    {
        return $this->resourceAvailabilities->map(fn (ResourceAvailability $r) => $r->resourceId)->findFirst();
    }

    public function blockedEntirelyBy(Owner $owner): bool
    {
        return $this->resourceAvailabilities->allMatch(fn (ResourceAvailability $r) => $r->blockedBy()->equals($owner));
    }

    public function isDisabledEntirelyBy(Owner $owner): bool
    {
        return $this->resourceAvailabilities->allMatch(fn (ResourceAvailability $r) => $r->isDisabledBy($owner));
    }

    /**
     * @return GenericList<ResourceAvailability>
     */
    public function findBlockedBy(Owner $owner): GenericList
    {
        return $this->resourceAvailabilities->filter(fn (ResourceAvailability $r) => $r->blockedBy()->equals($owner));
    }

    public function isEntirelyAvailable(): bool
    {
        return $this->resourceAvailabilities->allMatch(fn (ResourceAvailability $r) => $r->blockedBy()->byNone());
    }
}
