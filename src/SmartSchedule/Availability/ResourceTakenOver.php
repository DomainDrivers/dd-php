<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Shared\PublishedEvent;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;
use Symfony\Component\Uid\Uuid;

final readonly class ResourceTakenOver implements PublishedEvent
{
    /**
     * @param Set<Owner> $previousOwners
     */
    public function __construct(
        public Uuid $uuid,
        public ResourceId $resourceId,
        public Set $previousOwners,
        public TimeSlot $slot,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    /**
     * @param Set<Owner> $previousOwners
     */
    public static function new(ResourceId $resourceId, Set $previousOwners, TimeSlot $slot, \DateTimeImmutable $occurredAt): self
    {
        return new self(Uuid::v7(), $resourceId, $previousOwners, $slot, $occurredAt);
    }

    #[\Override]
    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
