<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Cashflow;

use Decimal\Decimal;

final readonly class Earnings implements \Stringable
{
    public function __construct(public Decimal $value)
    {
    }

    public static function of(int $value): self
    {
        return new self(new Decimal($value));
    }

    public static function fromString(string $value): self
    {
        return new self(new Decimal($value));
    }

    public function __toString(): string
    {
        return $this->value->toString();
    }

    public function greaterThan(self $other): bool
    {
        return $this->value->compareTo($other->value) > 0;
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }
}
