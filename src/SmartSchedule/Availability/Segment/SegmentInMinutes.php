<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability\Segment;

final readonly class SegmentInMinutes
{
    private function __construct(public int $minutes)
    {
    }

    public static function of(int $minutes, int $slotDurationInMinutes = Segments::DEFAULT_SEGMENT_DURATION_IN_MINUTES): self
    {
        if ($minutes <= 0) {
            throw new \InvalidArgumentException('SegmentInMinutesDuration must be positive');
        }
        if ($minutes < $slotDurationInMinutes) {
            throw new \InvalidArgumentException(sprintf('SegmentInMinutesDuration must be at least %s minutes', $slotDurationInMinutes));
        }
        if ($minutes % $slotDurationInMinutes != 0) {
            throw new \InvalidArgumentException(sprintf('SegmentInMinutesDuration must be a multiple of %s', $slotDurationInMinutes));
        }

        return new self($minutes);
    }

    public static function defaultSegment(): self
    {
        return new self(Segments::DEFAULT_SEGMENT_DURATION_IN_MINUTES);
    }
}
