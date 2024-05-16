<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Device\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use DomainDrivers\SmartSchedule\Resource\Device\Device;
use DomainDrivers\SmartSchedule\Resource\Device\DeviceId;
use DomainDrivers\SmartSchedule\Resource\Device\DeviceRepository;
use DomainDrivers\SmartSchedule\Resource\Device\DeviceSummary;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\GenericList;

final class OrmDeviceRepository implements DeviceRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[\Override]
    public function save(Device $device): Device
    {
        $this->entityManager->persist($device);
        $this->entityManager->flush();

        return $device;
    }

    #[\Override]
    public function findSummary(DeviceId $deviceId): DeviceSummary
    {
        $device = $this->entityManager->find(Device::class, $deviceId) ?? throw new \RuntimeException(sprintf('Device %s not found', $deviceId->toString()));

        return new DeviceSummary($device->id(), $device->model(), $device->capabilities());
    }

    #[\Override]
    public function findAllCapabilities(): GenericList
    {
        /** @var GenericList<Capability> $capabilities */
        $capabilities = GenericList::empty();
        foreach ($this->entityManager->getRepository(Device::class)->findAll() as $device) {
            $capabilities = $capabilities->appendAll($device->capabilities());
        }

        /** @var GenericList<Capability> $capabilities */
        return $capabilities;
    }
}
