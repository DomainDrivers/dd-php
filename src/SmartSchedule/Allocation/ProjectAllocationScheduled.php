<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Shared\PrivateEvent;
use DomainDrivers\SmartSchedule\Shared\PublishedEvent;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Symfony\Component\Uid\Uuid;

final readonly class ProjectAllocationScheduled implements PublishedEvent, PrivateEvent
{
    public function __construct(
        public Uuid $uuid,
        public ProjectAllocationsId $projectId,
        public TimeSlot $fromTo,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    public static function new(ProjectAllocationsId $projectId, TimeSlot $fromTo, \DateTimeImmutable $occurredAt): self
    {
        return new self(Uuid::v7(), $projectId, $fromTo, $occurredAt);
    }

    #[\Override]
    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
