<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Simulation;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\SmartSchedule\Simulation\AvailableResourceCapability;
use DomainDrivers\SmartSchedule\Simulation\SimulatedCapabilities;
use Munus\Collection\GenericList;
use Symfony\Component\Uid\Uuid;

final class AvailableCapabilitiesBuilder
{
    private ?Uuid $currentResourceId = null;
    private Capability $capability;
    private TimeSlot $timeSlot;

    /**
     * @var GenericList<AvailableResourceCapability>
     */
    private GenericList $availabilities;

    public function __construct()
    {
        $this->availabilities = GenericList::empty();
    }

    public function withEmployee(Uuid $id): self
    {
        if ($this->currentResourceId instanceof Uuid) {
            $this->availabilities = $this->availabilities->append(new AvailableResourceCapability($this->currentResourceId, $this->capability, $this->timeSlot));
        }
        $this->currentResourceId = $id;

        return $this;
    }

    public function thatBrings(Capability $capability): self
    {
        $this->capability = $capability;

        return $this;
    }

    public function thatIsAvailableAt(TimeSlot $timeSlot): self
    {
        $this->timeSlot = $timeSlot;

        return $this;
    }

    public function build(): SimulatedCapabilities
    {
        if ($this->currentResourceId instanceof Uuid) {
            $this->availabilities = $this->availabilities->append(new AvailableResourceCapability($this->currentResourceId, $this->capability, $this->timeSlot));
        }

        return new SimulatedCapabilities($this->availabilities);
    }
}
