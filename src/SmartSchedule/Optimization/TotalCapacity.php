<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Optimization;

use Munus\Collection\GenericList;
use Munus\Collection\Stream\Collectors;
use Munus\Collection\Traversable;

final readonly class TotalCapacity
{
    /**
     * @param GenericList<covariant CapacityDimension> $components
     */
    public function __construct(public GenericList $components)
    {
    }

    public static function zero(): self
    {
        return new self(GenericList::empty());
    }

    public static function of(CapacityDimension ...$dimensions): self
    {
        return new self(GenericList::ofAll($dimensions));
    }

    public function size(): int
    {
        return $this->components->length();
    }

    /**
     * @param Traversable<CapacityDimension> $capacities
     */
    public function add(Traversable $capacities): self
    {
        return new self($this->components->toStream()->appendAll($capacities)->collect(Collectors::toList()));
    }
}
