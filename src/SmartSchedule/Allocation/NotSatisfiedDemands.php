<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Shared\PublishedEvent;
use Munus\Collection\Map;
use Symfony\Component\Uid\Uuid;

final readonly class NotSatisfiedDemands implements PublishedEvent
{
    /**
     * @param Map<string, Demands> $missingDemands
     */
    public function __construct(
        public Uuid $eventId,
        public Map $missingDemands,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    /**
     * @param Map<string, Demands> $missingDemands
     */
    public static function new(Map $missingDemands, \DateTimeImmutable $occurredAt): self
    {
        return new self(Uuid::v7(), $missingDemands, $occurredAt);
    }

    public static function forOneProject(ProjectAllocationsId $projectId, Demands $scheduledDemands, \DateTimeImmutable $occurredAt): self
    {
        return new self(Uuid::v7(), Map::fromArray([$projectId->toString() => $scheduledDemands]), $occurredAt);
    }

    public static function allSatisfied(ProjectAllocationsId $projectId, \DateTimeImmutable $occurredAt): self
    {
        return new self(Uuid::v7(), Map::fromArray([$projectId->toString() => Demands::none()]), $occurredAt);
    }

    #[\Override]
    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
