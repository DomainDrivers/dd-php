<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Map;
use Munus\Collection\Stream\Collectors;

final readonly class ProjectsAllocationsSummary
{
    /**
     * @param Map<string, TimeSlot>    $timeSlots
     * @param Map<string, Allocations> $projectAllocations
     * @param Map<string, Demands>     $demands
     */
    public function __construct(
        public Map $timeSlots,
        public Map $projectAllocations,
        public Map $demands
    ) {
    }

    /**
     * @param GenericList<ProjectAllocations> $allProjectAllocations
     */
    public static function of(GenericList $allProjectAllocations): self
    {
        $keyMapper = fn (ProjectAllocations $p) => $p->id()->toString();

        return new self(
            $allProjectAllocations->toStream()
                ->filter(fn (ProjectAllocations $p) => $p->hasTimeSlot())
                ->collect(Collectors::toMap($keyMapper, fn (ProjectAllocations $p) => $p->timeSlot())),
            $allProjectAllocations->toStream()
                ->collect(Collectors::toMap($keyMapper, fn (ProjectAllocations $p) => $p->allocations())),
            $allProjectAllocations->toStream()
                ->collect(Collectors::toMap($keyMapper, fn (ProjectAllocations $p) => $p->demands()))
        );
    }
}
