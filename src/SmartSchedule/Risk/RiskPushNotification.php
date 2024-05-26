<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Risk;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilitiesSummary;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Planning\Demands;
use DomainDrivers\SmartSchedule\Planning\ProjectId;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Map;
use Munus\Collection\Set;

class RiskPushNotification
{
    public function notifyDemandsSatisfied(ProjectAllocationsId $projectId): void
    {
    }

    /**
     * @param Map<string, AllocatableCapabilitiesSummary> $available
     */
    public function notifyAboutAvailability(ProjectAllocationsId $projectId, Map $available): void
    {
    }

    public function notifyProfitableRelocationFound(ProjectAllocationsId $projectId, AllocatableCapabilityId $allocatableCapabilityId): void
    {
    }

    public function notifyAboutPossibleRisk(ProjectAllocationsId $projectId): void
    {
    }

    public function notifyAboutPossibleRiskDuringPlanning(ProjectId $cause, Demands $demands): void
    {
    }

    public function notifyAboutCriticalResourceNotAvailable(ProjectId $cause, ResourceId $criticalResource, TimeSlot $timeSlot): void
    {
    }

    /**
     * @param Set<ResourceId> $notAvailable
     */
    public function notifyAboutResourcesNotAvailable(ProjectId $projectId, Set $notAvailable): void
    {
    }
}
