<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use Munus\Collection\GenericList;
use Munus\Collection\Stream\Collectors;

final readonly class ParallelStagesList
{
    /**
     * @param GenericList<ParallelStages> $all
     */
    public function __construct(private GenericList $all)
    {
    }

    public static function empty(): self
    {
        return new self(GenericList::empty());
    }

    public static function of(ParallelStages ...$stages): self
    {
        return new self(GenericList::ofAll($stages));
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

    /**
     * @param ?callable(ParallelStages, ParallelStages): int $comparator
     *
     * @return GenericList<ParallelStages>
     */
    public function allSorted(?callable $comparator = null): GenericList
    {
        $all = $this->all->toArray();
        uasort($all, $comparator ?? fn (ParallelStages $a, ParallelStages $b) => $a->print() <=> $b->print());

        return GenericList::ofAll($all);
    }
}
