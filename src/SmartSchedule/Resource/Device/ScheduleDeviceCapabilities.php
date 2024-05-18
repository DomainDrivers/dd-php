<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Device;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityScheduler;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;

final readonly class ScheduleDeviceCapabilities
{
    public function __construct(private DeviceRepository $deviceRepository, private CapabilityScheduler $capabilityScheduler)
    {
    }

    /**
     * @return GenericList<AllocatableCapabilityId>
     */
    public function setupDeviceCapabilities(DeviceId $deviceId, TimeSlot $timeSlot): GenericList
    {
        $summary = $this->deviceRepository->findSummary($deviceId);

        return $this->capabilityScheduler->scheduleResourceCapabilitiesForPeriod(
            $deviceId->toAllocatableResourceId(),
            GenericList::of(CapabilitySelector::canPerformAllAtTheTime($summary->assets)),
            $timeSlot
        );
    }
}
