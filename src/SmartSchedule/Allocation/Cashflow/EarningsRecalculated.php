<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Cashflow;

use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Shared\Event;
use Symfony\Component\Uid\Uuid;

final readonly class EarningsRecalculated implements Event
{
    public function __construct(
        public Uuid $uuid,
        public ProjectAllocationsId $projectId,
        public Earnings $earnings,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    public static function new(ProjectAllocationsId $projectId, Earnings $earnings, \DateTimeImmutable $occurredAt): self
    {
        return new self(Uuid::v7(), $projectId, $earnings, $occurredAt);
    }

    #[\Override]
    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
