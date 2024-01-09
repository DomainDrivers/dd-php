<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use Munus\Collection\Set;
use Munus\Collection\Stream\Collectors;

final readonly class ParallelStages
{
    /**
     * @param Set<Stage> $stages
     */
    public function __construct(private Set $stages)
    {
    }

    public function print(): string
    {
        return $this->stages
            ->map(fn (Stage $stage) => $stage->name())
            ->sorted()
            ->collect(Collectors::joining(', '));
    }
}
