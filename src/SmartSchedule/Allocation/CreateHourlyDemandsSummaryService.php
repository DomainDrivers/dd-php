<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use Munus\Collection\GenericList;
use Munus\Collection\Stream\Collectors;

final readonly class CreateHourlyDemandsSummaryService
{
    /**
     * @param GenericList<ProjectAllocations> $projectAllocations
     */
    public function create(GenericList $projectAllocations, \DateTimeImmutable $when): NotSatisfiedDemands
    {
        return NotSatisfiedDemands::new($projectAllocations->toStream()->collect(Collectors::toMap(
            fn (ProjectAllocations $p) => $p->id()->toString(),
            fn (ProjectAllocations $p) => $p->missingDemands()
        )), $when);
    }
}
