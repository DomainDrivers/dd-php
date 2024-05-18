<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;
use Munus\Collection\Stream\Collectors;
use Munus\Control\Option;

final readonly class Allocations
{
    /**
     * @param Set<AllocatedCapability> $all
     */
    public function __construct(public Set $all)
    {
    }

    public static function none(): self
    {
        return new self(Set::empty());
    }

    public function add(AllocatedCapability $newOne): self
    {
        return new self($this->all->add($newOne));
    }

    public function remove(AllocatableCapabilityId $toRemove, TimeSlot $slot): self
    {
        return $this->find($toRemove)
            ->map(fn (AllocatedCapability $ac) => $this->removeFromSlot($ac, $slot))
            ->getOrElse($this)
        ;
    }

    /**
     * @return Option<AllocatedCapability>
     */
    public function find(AllocatableCapabilityId $allocatedCapabilityId): Option
    {
        return $this->all->find(fn (AllocatedCapability $ac) => $ac->allocatedCapabilityID->id->equals($allocatedCapabilityId->id));
    }

    private function removeFromSlot(AllocatedCapability $allocatedCapability, TimeSlot $slot): self
    {
        $leftOvers = $allocatedCapability
            ->timeSlot
            ->leftoverAfterRemovingCommonWith($slot)
            ->toStream()
            ->filter(fn (TimeSlot $leftOver) => $leftOver->within($allocatedCapability->timeSlot))
            ->map(fn (TimeSlot $leftOver) => new AllocatedCapability($allocatedCapability->allocatedCapabilityID, $allocatedCapability->capability, $leftOver))
            ->collect(Collectors::toSet());
        $newSlots = $this->all->remove($allocatedCapability);
        /** @var AllocatedCapability $leftOver */
        foreach ($leftOvers->toArray() as $leftOver) {
            $newSlots = $newSlots->add($leftOver);
        }

        return new self($newSlots);
    }
}
