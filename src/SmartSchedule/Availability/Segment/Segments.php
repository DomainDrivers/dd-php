<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability\Segment;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;

final readonly class Segments
{
    public const int DEFAULT_SEGMENT_DURATION_IN_MINUTES = 15;

    /**
     * @return GenericList<TimeSlot>
     */
    public static function split(TimeSlot $timeSlot, SegmentInMinutes $unit): GenericList
    {
        $normalizedSlot = self::normalizeToSegmentBoundaries($timeSlot, $unit);

        return (new SlotToSegments())($normalizedSlot, $unit);
    }

    public static function normalizeToSegmentBoundaries(TimeSlot $timeSlot, SegmentInMinutes $unit): TimeSlot
    {
        return (new SlotToNormalizedSlot())($timeSlot, $unit);
    }
}
