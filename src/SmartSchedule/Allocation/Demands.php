<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

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

    public function missingDemands(Allocations $allocations): self
    {
        return new self($this->all->filter(fn (Demand $d) => !$this->satisfiedBy($d, $allocations)));
    }

    private function satisfiedBy(Demand $d, Allocations $allocations): bool
    {
        return !$allocations->all->find(
            fn (AllocatedCapability $ac) => $ac->capability->equals($d->capability) && $d->slot->within($ac->timeSlot)
        )->isEmpty();
    }
}
