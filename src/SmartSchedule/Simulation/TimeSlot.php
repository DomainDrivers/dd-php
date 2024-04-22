<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

final readonly class TimeSlot
{
    public function __construct(public \DateTimeImmutable $from, public \DateTimeImmutable $to)
    {
    }

    public static function createDailyTimeSlotAtUTC(int $year, int $month, int $day): self
    {
        return new self(
            new \DateTimeImmutable(\sprintf('%s-%s-%s 00:00:00', $year, $month, $day), new \DateTimeZone('UTC')),
            new \DateTimeImmutable(\sprintf('%s-%s-%s 23:59:59', $year, $month, $day), new \DateTimeZone('UTC'))
        );
    }

    public function within(self $other): bool
    {
        return $this->from >= $other->from && $this->to <= $other->to;
    }
}
