<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use Munus\Collection\GenericList;

final readonly class Demands
{
    /**
     * @param GenericList<Demand> $all
     */
    public function __construct(public GenericList $all)
    {
    }

    public static function of(Demand ...$demands): self
    {
        return new self(GenericList::ofAll($demands));
    }
}
