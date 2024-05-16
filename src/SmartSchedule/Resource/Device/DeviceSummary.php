<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Device;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\Set;

final readonly class DeviceSummary
{
    /**
     * @param Set<Capability> $assets
     */
    public function __construct(public DeviceId $id, public string $model, public Set $assets)
    {
    }
}
