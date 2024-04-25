<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Planning\Schedule\Assertions;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use PHPUnit\Framework\Assert;

final class StageAssert extends Assert
{
    public function __construct(private readonly TimeSlot $actual, private readonly ScheduleAssert $scheduleAssert)
    {
    }

    public function thatStarts(\DateTimeImmutable $start): self
    {
        self::assertTrue($this->actual->from == $start);

        return $this;
    }

    public function thatEnds(\DateTimeImmutable $end): self
    {
        self::assertTrue($this->actual->to == $end);

        return $this;
    }

    public function withSlot(TimeSlot $slot): self
    {
        if ($this->actual != $slot) {
            var_dump($this->actual);
            var_dump($slot);
        }
        self::assertTrue($this->actual == $slot);

        return $this;
    }

    public function and(): ScheduleAssert
    {
        return $this->scheduleAssert;
    }

    public function isBefore(string $stage): self
    {
        /** @var TimeSlot $timeSlot */
        $timeSlot = $this->scheduleAssert->schedule()->dates->get($stage)->get();
        self::assertTrue($this->actual->to <= $timeSlot->from);

        return $this;
    }

    public function startsTogetherWith(string $stage): self
    {
        /** @var TimeSlot $timeSlot */
        $timeSlot = $this->scheduleAssert->schedule()->dates->get($stage)->get();
        self::assertTrue($this->actual->from == $timeSlot->from);

        return $this;
    }

    public function isAfter(string $stage): self
    {
        /** @var TimeSlot $timeSlot */
        $timeSlot = $this->scheduleAssert->schedule()->dates->get($stage)->get();
        self::assertTrue($this->actual->from >= $timeSlot->to);

        return $this;
    }
}
