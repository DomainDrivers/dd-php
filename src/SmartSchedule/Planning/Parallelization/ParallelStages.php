<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
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

    public static function of(Stage ...$stages): self
    {
        return new self(Set::ofAll($stages));
    }

    public function print(): string
    {
        return $this->stages
            ->map(fn (Stage $stage) => $stage->name())
            ->sorted()
            ->collect(Collectors::joining(', '));
    }

    /**
     * @return Set<Stage>
     */
    public function stages(): Set
    {
        return $this->stages;
    }

    public function duration(): Duration
    {
        return new Duration($this->stages->map(fn (Stage $stage) => $stage->duration()->seconds)->max()->getOrElse(0));
    }
}
