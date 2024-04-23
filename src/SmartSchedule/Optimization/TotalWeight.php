<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Optimization;

use Munus\Collection\GenericList;

final readonly class TotalWeight
{
    /**
     * @param GenericList<covariant WeightDimension> $components
     */
    public function __construct(public GenericList $components)
    {
    }

    public static function zero(): self
    {
        return new self(GenericList::empty());
    }

    public static function of(WeightDimension ...$dimensions): self
    {
        return new self(GenericList::ofAll($dimensions));
    }
}
