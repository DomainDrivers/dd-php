<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Cashflow;

use Decimal\Decimal;

final readonly class Cost implements \Stringable
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
}
