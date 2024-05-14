<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability\Segment;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;

final class SlotToNormalizedSlot
{
    public function __invoke(TimeSlot $timeSlot, SegmentInMinutes $segmentInMinutes): TimeSlot
    {
        $segmentInMinutesDuration = $segmentInMinutes->minutes;
        $segmentStart = $this->normalizeStart($timeSlot->from, $segmentInMinutesDuration);
        $segmentEnd = $this->normalizeEnd($timeSlot->to, $segmentInMinutesDuration);
        $normalized = new TimeSlot($segmentStart, $segmentEnd);
        $minimalSegment = new TimeSlot($segmentStart, $segmentStart->modify(sprintf('+%s minutes', $segmentInMinutesDuration)));
        if ($normalized->within($minimalSegment)) {
            return $minimalSegment;
        }

        return $normalized;
    }

    private function normalizeEnd(\DateTimeImmutable $initialEnd, int $segmentInMinutesDuration): \DateTimeImmutable
    {
        $closestSegmentEnd = $initialEnd->setTime((int) $initialEnd->format('H'), 0);
        while ($initialEnd > $closestSegmentEnd) {
            $closestSegmentEnd = $closestSegmentEnd->modify(sprintf('+%s minutes', $segmentInMinutesDuration));
        }

        return $closestSegmentEnd;
    }

    private function normalizeStart(\DateTimeImmutable $initialStart, int $segmentInMinutesDuration): \DateTimeImmutable
    {
        $closestSegmentStart = $initialStart->setTime((int) $initialStart->format('H'), 0);
        if ($closestSegmentStart->modify(sprintf('+%s minutes', $segmentInMinutesDuration)) > $initialStart) {
            return $closestSegmentStart;
        }
        while ($closestSegmentStart < $initialStart) {
            $closestSegmentStart = $closestSegmentStart->modify(sprintf('+%s minutes', $segmentInMinutesDuration));
        }

        return $closestSegmentStart;
    }
}
