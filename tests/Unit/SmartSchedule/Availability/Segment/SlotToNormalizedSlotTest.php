<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Availability\Segment;

use DomainDrivers\SmartSchedule\Availability\Segment\SegmentInMinutes;
use DomainDrivers\SmartSchedule\Availability\Segment\SlotToNormalizedSlot;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SlotToNormalizedSlot::class)]
final class SlotToNormalizedSlotTest extends TestCase
{
    #[Test]
    public function hasNoEffectWhenSlotAlreadyNormalized(): void
    {
        // given
        $start = new \DateTimeImmutable('2023-09-09T00:00:00Z');
        $end = new \DateTimeImmutable('2023-09-09T01:00:00Z');
        $timeSlot = new TimeSlot($start, $end);
        $oneHour = SegmentInMinutes::of(60);

        // when
        $normalized = (new SlotToNormalizedSlot())($timeSlot, $oneHour);

        // then
        self::assertEquals($timeSlot, $normalized);
    }

    #[Test]
    public function normalizedToTheHour(): void
    {
        // given
        $start = new \DateTimeImmutable('2023-09-09T00:10:00Z');
        $end = new \DateTimeImmutable('2023-09-09T00:59:00Z');
        $timeSlot = new TimeSlot($start, $end);
        $oneHour = SegmentInMinutes::of(60);

        // when
        $normalized = (new SlotToNormalizedSlot())($timeSlot, $oneHour);

        // then
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:00:00Z'), $normalized->from);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T01:00:00Z'), $normalized->to);
    }

    #[Test]
    public function normalizedWhenShortSlotOverlappingTwoSegments(): void
    {
        // given
        $start = new \DateTimeImmutable('2023-09-09T00:29:00Z');
        $end = new \DateTimeImmutable('2023-09-09T00:31:00Z');
        $timeSlot = new TimeSlot($start, $end);
        $oneHour = SegmentInMinutes::of(60);

        // when
        $normalized = (new SlotToNormalizedSlot())($timeSlot, $oneHour);

        // then
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:00:00Z'), $normalized->from);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T01:00:00Z'), $normalized->to);
    }

    #[Test]
    public function noNormalizationWhenSlotStartsAtSegmentStart(): void
    {
        // given
        $start = new \DateTimeImmutable('2023-09-09T00:15:00Z');
        $end = new \DateTimeImmutable('2023-09-09T00:30:00Z');
        $timeSlot = new TimeSlot($start, $end);
        $start2 = new \DateTimeImmutable('2023-09-09T00:30:00Z');
        $end2 = new \DateTimeImmutable('2023-09-09T00:45:00Z');
        $timeSlot2 = new TimeSlot($start2, $end2);
        $fifteenMinutes = SegmentInMinutes::of(15);

        // when
        $normalized = (new SlotToNormalizedSlot())($timeSlot, $fifteenMinutes);
        $normalized2 = (new SlotToNormalizedSlot())($timeSlot2, $fifteenMinutes);

        // then
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:15:00Z'), $normalized->from);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:30:00Z'), $normalized->to);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:30:00Z'), $normalized2->from);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:45:00Z'), $normalized2->to);
    }
}
