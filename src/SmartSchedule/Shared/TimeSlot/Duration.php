<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\TimeSlot;

final readonly class Duration
{
    public function __construct(public int $seconds)
    {
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function ofDays(int $days): self
    {
        return new self($days * 86400);
    }

    public static function ofHours(int $hours): self
    {
        return new self($hours * 3600);
    }

    public function toDateInterval(): \DateInterval
    {
        return new \DateInterval(sprintf('PT%sS', $this->seconds));
    }

    public function plus(self $other): self
    {
        return new self($this->seconds + $other->seconds);
    }

    public function equals(self $other): bool
    {
        return $this->seconds === $other->seconds;
    }
}
