<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Shared\PublishedEvent;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Symfony\Component\Uid\Uuid;

final readonly class CriticalStagePlanned implements PublishedEvent
{
    public function __construct(
        public Uuid $uuid,
        public ProjectId $projectId,
        public TimeSlot $stageTimeSlot,
        public ?ResourceId $criticalResource,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    public static function new(ProjectId $projectId, TimeSlot $stageTimeSlot, ?ResourceId $criticalResource, \DateTimeImmutable $occurredAt): self
    {
        return new self(Uuid::v7(), $projectId, $stageTimeSlot, $criticalResource, $occurredAt);
    }

    #[\Override]
    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
