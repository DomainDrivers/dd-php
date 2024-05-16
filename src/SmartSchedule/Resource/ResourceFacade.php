<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource;

use DomainDrivers\SmartSchedule\Resource\Device\DeviceFacade;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeFacade;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\GenericList;
use Munus\Collection\Stream\Collectors;

final readonly class ResourceFacade
{
    public function __construct(private EmployeeFacade $employeeFacade, private DeviceFacade $deviceFacade)
    {
    }

    /**
     * @return GenericList<Capability>
     */
    public function findAllCapabilities(): GenericList
    {
        return $this->employeeFacade->findAllCapabilities()->toStream()->appendAll($this->deviceFacade->findAllCapabilities())->collect(Collectors::toList());
    }
}
