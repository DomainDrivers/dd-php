<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Sorter;

use Munus\Collection\GenericList;

final readonly class SortedNodes
{
    /**
     * @param GenericList<Nodes> $all
     */
    public function __construct(public GenericList $all)
    {
    }

    public static function empty(): self
    {
        return new self(GenericList::empty());
    }

    public function add(Nodes $nodes): self
    {
        return new self($this->all->append($nodes));
    }
}
