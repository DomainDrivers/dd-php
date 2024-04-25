<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Shared\TimeSlot;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
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

    #[Test]
    public function slotsOverlapping(): void
    {
        // given
        $slot1 = new TimeSlot(new \DateTimeImmutable('2023-01-01 00:00:00'), new \DateTimeImmutable('2023-01-10 00:00:00'));
        $slot2 = new TimeSlot(new \DateTimeImmutable('2023-01-05 00:00:00'), new \DateTimeImmutable('2023-01-15 00:00:00'));
        $slot3 = new TimeSlot(new \DateTimeImmutable('2023-01-10 00:00:00'), new \DateTimeImmutable('2023-01-20 00:00:00'));
        $slot4 = new TimeSlot(new \DateTimeImmutable('2023-01-05 00:00:00'), new \DateTimeImmutable('2023-01-10 00:00:00'));
        $slot5 = new TimeSlot(new \DateTimeImmutable('2023-01-01 00:00:00'), new \DateTimeImmutable('2023-01-10 00:00:00'));

        // expect
        self::assertTrue($slot1->overlapsWith($slot2));
        self::assertTrue($slot1->overlapsWith($slot1));
        self::assertTrue($slot1->overlapsWith($slot3));
        self::assertTrue($slot1->overlapsWith($slot4));
        self::assertTrue($slot1->overlapsWith($slot5));
    }

    #[Test]
    public function slotsNotOverlapping(): void
    {
        // given
        $slot1 = new TimeSlot(new \DateTimeImmutable('2023-01-01 00:00:00'), new \DateTimeImmutable('2023-01-10 00:00:00'));
        $slot2 = new TimeSlot(new \DateTimeImmutable('2023-01-10 01:00:00'), new \DateTimeImmutable('2023-01-20 00:00:00'));
        $slot3 = new TimeSlot(new \DateTimeImmutable('2023-01-11 00:00:00'), new \DateTimeImmutable('2023-01-20 00:00:00'));

        // expect
        self::assertFalse($slot1->overlapsWith($slot2));
        self::assertFalse($slot1->overlapsWith($slot3));
    }

    #[Test]
    public function removingCommonPartsShouldHaveNoEffectWhenThereIsNoOverlap(): void
    {
        // given
        $slot1 = new TimeSlot(new \DateTimeImmutable('2023-01-01 00:00:00'), new \DateTimeImmutable('2023-01-10 00:00:00'));
        $slot2 = new TimeSlot(new \DateTimeImmutable('2023-01-15 01:00:00'), new \DateTimeImmutable('2023-01-20 00:00:00'));

        // expect
        self::assertTrue($slot1->leftoverAfterRemovingCommonWith($slot2)->containsAll(GenericList::of($slot1, $slot2)));
    }

    #[Test]
    public function removingCommonPartsWhenThereIsFullOverlap(): void
    {
        // given
        $slot1 = new TimeSlot(new \DateTimeImmutable('2023-01-01 00:00:00'), new \DateTimeImmutable('2023-01-10 00:00:00'));

        // expect
        self::assertTrue($slot1->leftoverAfterRemovingCommonWith($slot1)->isEmpty());
    }

    #[Test]
    public function removingCommonPartsWhenThereIsOverlap(): void
    {
        // given
        $slot1 = new TimeSlot(new \DateTimeImmutable('2023-01-01 00:00:00'), new \DateTimeImmutable('2023-01-15 00:00:00'));
        $slot2 = new TimeSlot(new \DateTimeImmutable('2023-01-10 00:00:00'), new \DateTimeImmutable('2023-01-20 00:00:00'));

        // when
        $difference = $slot1->leftoverAfterRemovingCommonWith($slot2)->toArray();

        // then
        self::assertCount(2, $difference);
        self::assertEquals(new \DateTimeImmutable('2023-01-01 00:00:00'), $difference[0]->from);
        self::assertEquals(new \DateTimeImmutable('2023-01-10 00:00:00'), $difference[0]->to);
        self::assertEquals(new \DateTimeImmutable('2023-01-15 00:00:00'), $difference[1]->from);
        self::assertEquals(new \DateTimeImmutable('2023-01-20 00:00:00'), $difference[1]->to);

        // given
        $slot3 = new TimeSlot(new \DateTimeImmutable('2023-01-05 00:00:00'), new \DateTimeImmutable('2023-01-20 00:00:00'));
        $slot4 = new TimeSlot(new \DateTimeImmutable('2023-01-01 00:00:00'), new \DateTimeImmutable('2023-01-10 00:00:00'));

        // when
        $difference2 = $slot3->leftoverAfterRemovingCommonWith($slot4)->toArray();

        // then
        self::assertCount(2, $difference);
        self::assertEquals(new \DateTimeImmutable('2023-01-01 00:00:00'), $difference2[0]->from);
        self::assertEquals(new \DateTimeImmutable('2023-01-05 00:00:00'), $difference2[0]->to);
        self::assertEquals(new \DateTimeImmutable('2023-01-10 00:00:00'), $difference2[1]->from);
        self::assertEquals(new \DateTimeImmutable('2023-01-20 00:00:00'), $difference2[1]->to);
    }

    #[Test]
    public function removingCommonPartWhenOneSlotInFullyWithinAnother(): void
    {
        // given
        $slot1 = new TimeSlot(new \DateTimeImmutable('2023-01-01 00:00:00'), new \DateTimeImmutable('2023-01-20 00:00:00'));
        $slot2 = new TimeSlot(new \DateTimeImmutable('2023-01-10 00:00:00'), new \DateTimeImmutable('2023-01-15 00:00:00'));

        // when
        $difference = $slot1->leftoverAfterRemovingCommonWith($slot2)->toArray();

        // then
        self::assertCount(2, $difference);
        self::assertEquals(new \DateTimeImmutable('2023-01-01 00:00:00'), $difference[0]->from);
        self::assertEquals(new \DateTimeImmutable('2023-01-10 00:00:00'), $difference[0]->to);
        self::assertEquals(new \DateTimeImmutable('2023-01-15 00:00:00'), $difference[1]->from);
        self::assertEquals(new \DateTimeImmutable('2023-01-20 00:00:00'), $difference[1]->to);
    }
}
