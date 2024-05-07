<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use Symfony\Component\Uid\Uuid;

final readonly class CapabilityReleased
{
    public function __construct(
        public Uuid $eventId,
        public ProjectAllocationsId $projectId,
        public Demands $missingDemands,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    public static function new(
        ProjectAllocationsId $projectId,
        Demands $missingDemands,
        \DateTimeImmutable $occurredAt
    ): self {
        return new self(Uuid::v7(), $projectId, $missingDemands, $occurredAt);
    }
}
