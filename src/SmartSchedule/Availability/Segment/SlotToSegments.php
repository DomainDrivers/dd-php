<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability\Segment;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Stream;

final readonly class SlotToSegments
{
    /**
     * @return GenericList<TimeSlot>
     */
    public function __invoke(TimeSlot $timeSlot, SegmentInMinutes $duration): GenericList
    {
        $minimalSegment = new TimeSlot($timeSlot->from, $timeSlot->from->modify(sprintf('+%s minutes', $duration->minutes)));
        if ($timeSlot->within($minimalSegment)) {
            return GenericList::of($minimalSegment);
        }

        $segmentInMinutesDuration = $duration->minutes;
        $numberOfSegments = $this->calculateNumberOfSegments($timeSlot, $segmentInMinutesDuration);

        return Stream::iterate($timeSlot->from, fn (\DateTimeImmutable $current) => $current->modify(sprintf('+%s minutes', $segmentInMinutesDuration)))
            ->take($numberOfSegments)
            ->map(fn (\DateTimeImmutable $currentStart) => new TimeSlot($currentStart, $this->calculateEnd($segmentInMinutesDuration, $currentStart, $timeSlot->to)))
            ->collect(Stream\Collectors::toList())
        ;
    }

    private function calculateNumberOfSegments(TimeSlot $timeSlot, int $segmentInMinutesDuration): int
    {
        return (int) ceil(Duration::between($timeSlot->from, $timeSlot->to)->toMinutes() / $segmentInMinutesDuration);
    }

    private function calculateEnd(int $segmentInMinutesDuration, \DateTimeImmutable $currentStart, \DateTimeImmutable $initialEnd): \DateTimeImmutable
    {
        $segmentEnd = $currentStart->modify(sprintf('+%s minutes', $segmentInMinutesDuration));
        if ($initialEnd < $segmentEnd) {
            return $initialEnd;
        }

        return $segmentEnd;
    }
}
