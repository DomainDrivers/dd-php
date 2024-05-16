<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Device;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\GenericList;

interface DeviceRepository
{
    public function save(Device $device): Device;

    public function findSummary(DeviceId $deviceId): DeviceSummary;

    /**
     * @return GenericList<Capability>
     */
    public function findAllCapabilities(): GenericList;
}
