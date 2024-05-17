<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use DomainDrivers\SmartSchedule\Sorter\Edge;
use Munus\Collection\GenericList;

final readonly class RemovalSuggestion implements \Stringable
{
    /**
     * @param GenericList<Edge> $edges
     */
    public function __construct(public GenericList $edges)
    {
    }

    public function __toString(): string
    {
        return join(', ', $this->edges->toArray());
    }
}
