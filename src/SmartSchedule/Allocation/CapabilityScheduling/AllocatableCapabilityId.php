<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

use DomainDrivers\SmartSchedule\Availability\ResourceId;
use Munus\Value\Comparable;
use Symfony\Component\Uid\Uuid;

final readonly class AllocatableCapabilityId implements \Stringable, Comparable
{
    public function __construct(public Uuid $id)
    {
    }

    public static function newOne(): self
    {
        return new self(Uuid::v7());
    }

    public static function fromString(string $id): self
    {
        return new self(Uuid::fromString($id));
    }

    public static function fromResourceId(ResourceId $resourceId): self
    {
        return new self($resourceId->getId());
    }

    public function toAvailabilityResourceId(): ResourceId
    {
        return new ResourceId($this->id);
    }

    public function toString(): string
    {
        return $this->id->toRfc4122();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->toString();
    }

    #[\Override]
    public function equals(Comparable $other): bool
    {
        return self::class === $other::class && $this->id->equals($other->id);
    }
}
