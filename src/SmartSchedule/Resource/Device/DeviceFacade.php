<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Device;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\GenericList;
use Munus\Collection\Set;

final readonly class DeviceFacade
{
    public function __construct(private DeviceRepository $deviceRepository)
    {
    }

    public function findDevice(DeviceId $deviceId): DeviceSummary
    {
        return $this->deviceRepository->findSummary($deviceId);
    }

    /**
     * @return GenericList<Capability>
     */
    public function findAllCapabilities(): GenericList
    {
        return $this->deviceRepository->findAllCapabilities();
    }

    /**
     * @param Set<Capability> $assets
     */
    public function createDevice(string $model, Set $assets): DeviceId
    {
        return $this->deviceRepository->save(new Device($model, $assets))->id();
    }
}
