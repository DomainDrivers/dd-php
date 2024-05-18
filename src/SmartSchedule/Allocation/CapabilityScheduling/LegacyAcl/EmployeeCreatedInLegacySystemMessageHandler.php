<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\LegacyAcl;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableResourceId;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityScheduler;

final readonly class EmployeeCreatedInLegacySystemMessageHandler
{
    public function __construct(private CapabilityScheduler $capabilityScheduler)
    {
    }

    // subscribe to message bus
    public function __invoke(EmployeeDataFromLegacyEsbMessage $message): void
    {
        $this->capabilityScheduler->scheduleResourceCapabilitiesForPeriod(
            new AllocatableResourceId($message->resourceId),
            (new TranslateToCapabilitySelector())->translate($message),
            $message->timeSlot
        );
    }
}
