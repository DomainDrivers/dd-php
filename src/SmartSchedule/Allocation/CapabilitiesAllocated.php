<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use Symfony\Component\Uid\Uuid;

final readonly class CapabilitiesAllocated
{
    public function __construct(
        public Uuid $eventId,
        public Uuid $allocatedCapabilityId,
        public ProjectAllocationsId $projectId,
        public Demands $missingDemands,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    public static function new(
        Uuid $allocatedCapabilityId,
        ProjectAllocationsId $projectId,
        Demands $missingDemands,
        \DateTimeImmutable $occurredAt
    ): self {
        return new self(Uuid::v7(), $allocatedCapabilityId, $projectId, $missingDemands, $occurredAt);
    }
}
