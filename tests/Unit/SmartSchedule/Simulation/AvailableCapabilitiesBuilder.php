<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Simulation;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\SelectingPolicy;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\SmartSchedule\Simulation\AvailableResourceCapability;
use DomainDrivers\SmartSchedule\Simulation\SimulatedCapabilities;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use Symfony\Component\Uid\Uuid;

final class AvailableCapabilitiesBuilder
{
    private ?Uuid $currentResourceId = null;
    /**
     * @var Set<Capability>
     */
    private Set $capabilities;
    private TimeSlot $timeSlot;
    private SelectingPolicy $selectingPolicy;

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
            $this->availabilities = $this->availabilities->append(new AvailableResourceCapability($this->currentResourceId, new CapabilitySelector($this->capabilities, $this->selectingPolicy), $this->timeSlot));
        }
        $this->currentResourceId = $id;

        return $this;
    }

    public function thatBrings(Capability $capability): self
    {
        $this->capabilities = Set::of($capability);
        $this->selectingPolicy = SelectingPolicy::ONE_OF_ALL;

        return $this;
    }

    public function thatBringsSimultaneously(Capability ...$skills): self
    {
        $this->capabilities = Set::ofAll($skills);
        $this->selectingPolicy = SelectingPolicy::ALL_SIMULTANEOUSLY;

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
            $this->availabilities = $this->availabilities->append(new AvailableResourceCapability($this->currentResourceId, new CapabilitySelector($this->capabilities, $this->selectingPolicy), $this->timeSlot));
        }

        return new SimulatedCapabilities($this->availabilities);
    }
}
