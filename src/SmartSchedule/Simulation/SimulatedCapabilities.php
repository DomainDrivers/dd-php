<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use Munus\Collection\GenericList;
use Munus\Collection\Stream\Collectors;

final readonly class SimulatedCapabilities
{
    /**
     * @param GenericList<AvailableResourceCapability> $capabilities
     */
    public function __construct(public GenericList $capabilities)
    {
    }

    public function add(AvailableResourceCapability $capability): self
    {
        return new self($this->capabilities->append($capability));
    }

    /**
     * @param GenericList<AvailableResourceCapability> $capabilities
     */
    public function addAll(GenericList $capabilities): self
    {
        return new self($this->capabilities->toStream()->appendAll($capabilities)->collect(Collectors::toList()));
    }
}
