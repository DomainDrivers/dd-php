<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Stream;
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

    public static function allInSameTimeSlot(TimeSlot $slot, Capability ...$capabilities): self
    {
        return new self(Stream::ofAll($capabilities)
            ->map(fn (Capability $c) => new Demand($c, $slot))
            ->collect(Collectors::toList()));
    }

    public function missingDemands(Allocations $allocations): self
    {
        return new self($this->all->filter(fn (Demand $d) => !$this->satisfiedBy($d, $allocations)));
    }

    public function withNew(Demands $newDemands): self
    {
        return new self($this->all->toStream()->appendAll($newDemands->all)->collect(Collectors::toList()));
    }

    private function satisfiedBy(Demand $d, Allocations $allocations): bool
    {
        return !$allocations->all->find(
            fn (AllocatedCapability $ac) => $ac->capability->equals($d->capability) && $d->slot->within($ac->timeSlot)
        )->isEmpty();
    }
}
