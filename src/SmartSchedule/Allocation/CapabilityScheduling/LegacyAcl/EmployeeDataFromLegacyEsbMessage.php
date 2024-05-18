<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\LegacyAcl;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Symfony\Component\Uid\Uuid;

final readonly class EmployeeDataFromLegacyEsbMessage
{
    /**
     * @param GenericList<GenericList<string>> $skillsPerformedTogether
     * @param GenericList<string>              $exclusiveSkills
     * @param GenericList<string>              $permissions
     */
    public function __construct(
        public Uuid $resourceId,
        public GenericList $skillsPerformedTogether,
        public GenericList $exclusiveSkills,
        public GenericList $permissions,
        public TimeSlot $timeSlot
    ) {
    }
}
