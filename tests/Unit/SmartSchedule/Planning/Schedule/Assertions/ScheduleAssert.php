<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Planning\Schedule\Assertions;

use DomainDrivers\SmartSchedule\Planning\Schedule\Schedule;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use PHPUnit\Framework\Assert;

final class ScheduleAssert extends Assert
{
    public function __construct(private readonly Schedule $actual)
    {
    }

    public function hasStages(int $number): self
    {
        self::assertSame($number, $this->actual->dates->length());

        return $this;
    }

    public function hasStage(string $name): StageAssert
    {
        $timeSlot = $this->actual->dates->get($name)->getOrNull();
        self::assertInstanceOf(TimeSlot::class, $timeSlot);

        return new StageAssert($timeSlot, $this);
    }

    public function isScheduleEmpty(): self
    {
        self::assertTrue($this->actual->dates->isEmpty());

        return $this;
    }

    public function schedule(): Schedule
    {
        return $this->actual;
    }
}
