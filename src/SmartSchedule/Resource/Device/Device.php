<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Device;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Version;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\Set;

#[Entity]
#[Table('devices')]
final class Device
{
    #[Id]
    #[Column(type: 'device_id')]
    private DeviceId $deviceId;

    #[Version]
    #[Column(type: 'bigint')]
    private int $version;

    #[Column(type: 'string')]
    private string $model;

    /**
     * @var Set<Capability> $capabilities
     */
    #[Column(type: 'capabilities', options: ['jsonb' => true])]
    private Set $capabilities;

    /**
     * @param Set<Capability> $capabilities
     */
    public function __construct(string $model, Set $capabilities)
    {
        $this->deviceId = DeviceId::newOne();
        $this->model = $model;
        $this->capabilities = $capabilities;
    }

    public function id(): DeviceId
    {
        return $this->deviceId;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * @return Set<Capability>
     */
    public function capabilities(): Set
    {
        return $this->capabilities;
    }
}
