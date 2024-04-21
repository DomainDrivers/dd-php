<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Sorter;

use Munus\Collection\GenericList;

/**
 * @template T
 */
final readonly class SortedNodes
{
    /**
     * @param GenericList<Nodes<T>> $all
     */
    public function __construct(public GenericList $all)
    {
    }

    /**
     * @return self<T>
     */
    public static function empty(): self
    {
        return new self(GenericList::empty());
    }

    /**
     * @param Nodes<T> $nodes
     *
     * @return self<T>
     */
    public function add(Nodes $nodes): self
    {
        return new self($this->all->append($nodes));
    }
}
