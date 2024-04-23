<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Shared\TimeSlot;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimeSlot::class)]
final class TimeSlotTest extends TestCase
{
    #[Test]
    public function creatingMonthlyTimeSlotAtUTC(): void
    {
        // when
        $january2023 = TimeSlot::createMonthlyTimeSlotAtUTC(2023, 1);

        // then
        self::assertEquals(new \DateTimeImmutable('2023-01-01 00:00:00'), $january2023->from);
        self::assertEquals(new \DateTimeImmutable('2023-01-31 23:59:59'), $january2023->to);
    }

    #[Test]
    public function creatingDailyTimeSlotAtUTC(): void
    {
        // when
        $givenDay = TimeSlot::createDailyTimeSlotAtUTC(2023, 1, 15);

        // then
        self::assertEquals(new \DateTimeImmutable('2023-01-15 00:00:00'), $givenDay->from);
        self::assertEquals(new \DateTimeImmutable('2023-01-15 23:59:59'), $givenDay->to);
    }

    #[Test]
    public function oneSlotWithinAnother(): void
    {
        // given
        $slot1 = new TimeSlot(new \DateTimeImmutable('2023-01-02 00:00:00'), new \DateTimeImmutable('2023-01-02 23:59:59'));
        $slot2 = new TimeSlot(new \DateTimeImmutable('2023-01-01 00:00:00'), new \DateTimeImmutable('2023-01-03 00:00:00'));

        // expect
        self::assertTrue($slot1->within($slot2));
        self::assertFalse($slot2->within($slot1));
    }

    #[Test]
    public function oneSlotIsNotWithinAnotherIfTheyJustOverlap(): void
    {
        // given
        $slot1 = new TimeSlot(new \DateTimeImmutable('2023-01-01 00:00:00'), new \DateTimeImmutable('2023-01-02 23:59:59'));
        $slot2 = new TimeSlot(new \DateTimeImmutable('2023-01-02 00:00:00'), new \DateTimeImmutable('2023-01-03 00:00:00'));

        // expect
        self::assertFalse($slot1->within($slot2));
        self::assertFalse($slot2->within($slot1));

        // given
        $slot3 = new TimeSlot(new \DateTimeImmutable('2023-01-02 00:00:00'), new \DateTimeImmutable('2023-01-03 23:59:59'));
        $slot4 = new TimeSlot(new \DateTimeImmutable('2023-01-01 00:00:00'), new \DateTimeImmutable('2023-01-02 23:59:59'));

        // expect
        self::assertFalse($slot3->within($slot4));
        self::assertFalse($slot4->within($slot3));
    }

    #[Test]
    public function slotIsNotWithinAnotherWhenTheyAreCompletelyOutside(): void
    {
        // given
        $slot1 = new TimeSlot(new \DateTimeImmutable('2023-01-01 00:00:00'), new \DateTimeImmutable('2023-01-01 23:59:59'));
        $slot2 = new TimeSlot(new \DateTimeImmutable('2023-01-02 00:00:00'), new \DateTimeImmutable('2023-01-03 00:00:00'));

        // expect
        self::assertFalse($slot1->within($slot2));
    }

    #[Test]
    public function slotIsWithinItself(): void
    {
        // given
        $slot1 = new TimeSlot(new \DateTimeImmutable('2023-01-01 00:00:00'), new \DateTimeImmutable('2023-01-01 23:59:59'));

        // expect
        self::assertTrue($slot1->within($slot1));
    }
}
