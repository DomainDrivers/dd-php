<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\TimeSlot;

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

    public static function createMonthlyTimeSlotAtUTC(int $year, int $month): self
    {
        $from = new \DateTimeImmutable(\sprintf('%s-%s-01 00:00:00', $year, $month), new \DateTimeZone('UTC'));

        return new self(
            $from,
            $from->modify('last day of')->setTime(23, 59, 59)
        );
    }

    public function within(self $other): bool
    {
        return $this->from >= $other->from && $this->to <= $other->to;
    }
}
