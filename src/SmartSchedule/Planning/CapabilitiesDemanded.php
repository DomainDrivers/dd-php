<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Shared\Event;
use Symfony\Component\Uid\Uuid;

final readonly class CapabilitiesDemanded implements Event
{
    public function __construct(
        public Uuid $uuid,
        public ProjectId $projectId,
        public Demands $demands,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    public static function new(ProjectId $projectId, Demands $demands, \DateTimeImmutable $occurredAt): self
    {
        return new self(Uuid::v7(), $projectId, $demands, $occurredAt);
    }

    #[\Override]
    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
