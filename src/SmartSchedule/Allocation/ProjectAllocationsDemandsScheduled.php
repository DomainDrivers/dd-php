<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Shared\PrivateEvent;
use Symfony\Component\Uid\Uuid;

final readonly class ProjectAllocationsDemandsScheduled implements PrivateEvent
{
    public function __construct(
        public Uuid $uuid,
        public ProjectAllocationsId $projectId,
        public Demands $missingDemands,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    public static function new(ProjectAllocationsId $projectId, Demands $missingDemands, \DateTimeImmutable $occurredAt): self
    {
        return new self(Uuid::v7(), $projectId, $missingDemands, $occurredAt);
    }

    #[\Override]
    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
