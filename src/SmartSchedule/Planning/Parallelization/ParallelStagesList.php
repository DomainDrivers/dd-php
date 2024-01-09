<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use Munus\Collection\GenericList;
use Munus\Collection\Stream\Collectors;

final class ParallelStagesList
{
    /**
     * @param GenericList<ParallelStages> $all
     */
    private function __construct(private GenericList $all)
    {
    }

    public static function empty(): self
    {
        return new self(GenericList::empty());
    }

    public function add(ParallelStages $new): self
    {
        return new self($this->all->append($new));
    }

    public function print(): string
    {
        return $this->all
            ->map(fn (ParallelStages $stages) => $stages->print())
            ->collect(Collectors::joining(' | '));
    }

    /**
     * @return GenericList<ParallelStages>
     */
    public function all(): GenericList
    {
        return $this->all;
    }
}
