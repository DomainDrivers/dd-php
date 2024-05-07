<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use Munus\Collection\GenericList;
use Munus\Collection\Stream\Collectors;

final readonly class Demands
{
    /**
     * @param GenericList<Demand> $all
     */
    public function __construct(public GenericList $all)
    {
    }

    public static function none(): self
    {
        return new self(GenericList::empty());
    }

    public static function of(Demand ...$demands): self
    {
        return new self(GenericList::ofAll($demands));
    }

    public function add(self $demands): self
    {
        return new self($this->all->toStream()->appendAll($demands->all)->collect(Collectors::toList()));
    }
}
