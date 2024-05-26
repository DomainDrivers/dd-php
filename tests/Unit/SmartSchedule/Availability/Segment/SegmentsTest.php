<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Availability\Segment;

use DomainDrivers\SmartSchedule\Availability\Segment\SegmentInMinutes;
use DomainDrivers\SmartSchedule\Availability\Segment\Segments;
use DomainDrivers\SmartSchedule\Availability\Segment\SlotToSegments;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\Tests\Phpunit\AssertThrows;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Segments::class)]
final class SegmentsTest extends TestCase
{
    use AssertThrows;

    private const int FIFTEEN_MINUTES_SEGMENT_DURATION = 15;

    #[Test]
    public function unitHasToBeMultipleOfDefaultSlotDurationInMinutes(): void
    {
        self::assertThrows(\InvalidArgumentException::class, fn () => SegmentInMinutes::of(20, self::FIFTEEN_MINUTES_SEGMENT_DURATION));
        self::assertThrows(\InvalidArgumentException::class, fn () => SegmentInMinutes::of(18, self::FIFTEEN_MINUTES_SEGMENT_DURATION));
        self::assertThrows(\InvalidArgumentException::class, fn () => SegmentInMinutes::of(7, self::FIFTEEN_MINUTES_SEGMENT_DURATION));

        self::assertSame(15, SegmentInMinutes::of(15, self::FIFTEEN_MINUTES_SEGMENT_DURATION)->minutes);
        self::assertSame(30, SegmentInMinutes::of(30, self::FIFTEEN_MINUTES_SEGMENT_DURATION)->minutes);
        self::assertSame(45, SegmentInMinutes::of(45, self::FIFTEEN_MINUTES_SEGMENT_DURATION)->minutes);
    }

    #[Test]
    public function splittingIntoSegmentsWhenThereIsNoLeftover(): void
    {
        // given
        $start = new \DateTimeImmutable('2023-09-09T00:00:00Z');
        $end = new \DateTimeImmutable('2023-09-09T01:00:00Z');
        $timeSlot = new TimeSlot($start, $end);

        // when
        $segments = Segments::split($timeSlot, SegmentInMinutes::of(15, self::FIFTEEN_MINUTES_SEGMENT_DURATION))->toArray();

        // then
        self::assertCount(4, $segments);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:00:00Z'), $segments[0]->from);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:15:00Z'), $segments[0]->to);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:15:00Z'), $segments[1]->from);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:30:00Z'), $segments[1]->to);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:30:00Z'), $segments[2]->from);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:45:00Z'), $segments[2]->to);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:45:00Z'), $segments[3]->from);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T01:00:00Z'), $segments[3]->to);
    }

    #[Test]
    public function splittingIntoSegmentsJustNormalizesIfChosenSegmentLargerThanPassedSlot(): void
    {
        // given
        $start = new \DateTimeImmutable('2023-09-09T00:10:00Z');
        $end = new \DateTimeImmutable('2023-09-09T01:00:00Z');
        $timeSlot = new TimeSlot($start, $end);

        // when
        $segments = Segments::split($timeSlot, SegmentInMinutes::of(90, self::FIFTEEN_MINUTES_SEGMENT_DURATION))->toArray();

        // then
        self::assertCount(1, $segments);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:00:00Z'), $segments[0]->from);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T01:30:00Z'), $segments[0]->to);
    }

    #[Test]
    public function normalizingATimeSlot(): void
    {
        // given
        $start = new \DateTimeImmutable('2023-09-09T00:10:00Z');
        $end = new \DateTimeImmutable('2023-09-09T01:00:00Z');
        $timeSlot = new TimeSlot($start, $end);

        // when
        $segment = Segments::normalizeToSegmentBoundaries($timeSlot, SegmentInMinutes::of(90, self::FIFTEEN_MINUTES_SEGMENT_DURATION));

        // then
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:00:00Z'), $segment->from);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T01:30:00Z'), $segment->to);
    }

    #[Test]
    public function slotsAreNormalizedBeforeSplitting(): void
    {
        // given
        $start = new \DateTimeImmutable('2023-09-09T00:10:00Z');
        $end = new \DateTimeImmutable('2023-09-09T00:59:00Z');
        $timeSlot = new TimeSlot($start, $end);
        $oneHour = SegmentInMinutes::of(60, self::FIFTEEN_MINUTES_SEGMENT_DURATION);

        // when
        $segments = Segments::split($timeSlot, $oneHour)->toArray();

        // then
        self::assertCount(1, $segments);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:00:00Z'), $segments[0]->from);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T01:00:00Z'), $segments[0]->to);
    }

    #[Test]
    public function splittingIntoSegmentsWithoutNormalization(): void
    {
        // given
        $start = new \DateTimeImmutable('2023-09-09T00:00:00Z');
        $end = new \DateTimeImmutable('2023-09-09T00:59:00Z');
        $timeSlot = new TimeSlot($start, $end);

        // when
        $segments = (new SlotToSegments())($timeSlot, SegmentInMinutes::of(30, self::FIFTEEN_MINUTES_SEGMENT_DURATION))->toArray();

        // then
        self::assertCount(2, $segments);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:00:00Z'), $segments[0]->from);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:30:00Z'), $segments[0]->to);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:30:00Z'), $segments[1]->from);
        self::assertEquals(new \DateTimeImmutable('2023-09-09T00:59:00Z'), $segments[1]->to);
    }
}
