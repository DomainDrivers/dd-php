<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\TimeSlot;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Munus\Collection\GenericList;
use Munus\Value\Comparable;

#[Embeddable]
final readonly class TimeSlot implements Comparable
{
    public function __construct(
        #[Column(type: 'datetime_immutable')]
        public \DateTimeImmutable $from,
        #[Column(type: 'datetime_immutable')]
        public \DateTimeImmutable $to
    ) {
    }

    public static function empty(): self
    {
        return new self((new \DateTimeImmutable())->setTimestamp(0), (new \DateTimeImmutable())->setTimestamp(0));
    }

    public static function with(string $from, string $to): self
    {
        return new self(new \DateTimeImmutable($from), new \DateTimeImmutable($to));
    }

    public static function createDailyTimeSlotAtUTC(int $year, int $month, int $day): self
    {
        return new self(
            new \DateTimeImmutable(\sprintf('%s-%s-%s 00:00:00', $year, $month, $day), new \DateTimeZone('UTC')),
            new \DateTimeImmutable(\sprintf('%s-%s-%s 23:59:59', $year, $month, $day), new \DateTimeZone('UTC'))
        );
    }

    public static function createMonthlyTimeSlotAtUTC(int $year, int $month): self
    {
        $from = new \DateTimeImmutable(\sprintf('%s-%s-01 00:00:00', $year, $month), new \DateTimeZone('UTC'));

        return new self(
            $from,
            $from->modify('last day of')->setTime(23, 59, 59)
        );
    }

    public function within(self $other): bool
    {
        return $this->from >= $other->from && $this->to <= $other->to;
    }

    public function overlapsWith(self $other): bool
    {
        return $this->from <= $other->to && $this->to >= $other->from;
    }

    /**
     * @return GenericList<self>
     */
    public function leftoverAfterRemovingCommonWith(self $other): GenericList
    {
        if ($this == $other) {
            return GenericList::empty();
        }
        if (!$other->overlapsWith($this)) {
            return GenericList::of($this, $other);
        }
        $result = GenericList::empty();
        if ($this->from < $other->from) {
            $result = $result->append(new self($this->from, $other->from));
        }
        if ($other->from < $this->from) {
            $result = $result->append(new self($other->from, $this->from));
        }
        if ($this->to > $other->to) {
            $result = $result->append(new self($other->to, $this->to));
        }
        if ($other->to > $this->to) {
            $result = $result->append(new self($this->to, $other->to));
        }

        return $result;
    }

    public function commonPartWith(self $other): self
    {
        if (!$this->overlapsWith($other)) {
            return self::empty();
        }

        return new self(
            max($this->from, $other->from),
            min($this->to, $other->to)
        );
    }

    public function isEmpty(): bool
    {
        return $this->from->getTimestamp() === $this->to->getTimestamp();
    }

    public function duration(): Duration
    {
        return new Duration($this->to->getTimestamp() - $this->from->getTimestamp());
    }

    public function stretch(Duration $duration): self
    {
        return new self(
            $this->from->modify(sprintf('-%s seconds', $duration->seconds)),
            $this->to->modify(sprintf('+%s seconds', $duration->seconds)),
        );
    }

    #[\Override]
    public function equals(Comparable $other): bool
    {
        return self::class === $other::class && $this->from == $other->from && $this->to == $other->to;
    }
}
