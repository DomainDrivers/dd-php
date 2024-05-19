<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Shared\Event;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;
use Symfony\Component\Uid\Uuid;

final readonly class NeededResourcesChosen implements Event
{
    /**
     * @param Set<ResourceId> $neededResources
     */
    public function __construct(
        public Uuid $uuid,
        public ProjectId $projectId,
        public Set $neededResources,
        public TimeSlot $timeSlot,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    /**
     * @param Set<ResourceId> $neededResources
     */
    public static function new(ProjectId $projectId, Set $neededResources, TimeSlot $timeSlot, \DateTimeImmutable $occurredAt): self
    {
        return new self(Uuid::v7(), $projectId, $neededResources, $timeSlot, $occurredAt);
    }

    #[\Override]
    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
