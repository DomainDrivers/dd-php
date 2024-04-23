<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Optimization;

use Decimal\Decimal;

final readonly class Item
{
    public function __construct(
        public string $name,
        public Decimal $value,
        public TotalWeight $totalWeight
    ) {
    }

    public function isWeightZero(): bool
    {
        return $this->totalWeight->components->isEmpty();
    }
}
