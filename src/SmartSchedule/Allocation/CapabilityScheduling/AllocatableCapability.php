<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;

#[Entity]
#[Table('allocatable_capabilities')]
class AllocatableCapability
{
    #[Id]
    #[Column(type: 'allocatable_capability_id')]
    private AllocatableCapabilityId $id;

    #[Column(type: 'capability_selector', options: ['jsonb' => true])]
    private CapabilitySelector $possibleCapabilities;

    #[Column(type: 'allocatable_resource_id')]
    private AllocatableResourceId $resourceId;

    #[Embedded(class: TimeSlot::class, columnPrefix: 'date_')]
    private TimeSlot $timeSlot;

    public function __construct(AllocatableResourceId $resourceId, CapabilitySelector $possibleCapabilities, TimeSlot $timeSlot)
    {
        $this->id = AllocatableCapabilityId::newOne();
        $this->possibleCapabilities = $possibleCapabilities;
        $this->resourceId = $resourceId;
        $this->timeSlot = $timeSlot;
    }

    public function id(): AllocatableCapabilityId
    {
        return $this->id;
    }

    public function capabilities(): CapabilitySelector
    {
        return $this->possibleCapabilities;
    }

    public function resourceId(): AllocatableResourceId
    {
        return $this->resourceId;
    }

    public function timeSlot(): TimeSlot
    {
        return $this->timeSlot;
    }

    public function canPerform(Capability $capability): bool
    {
        return $this->possibleCapabilities->canPerform($capability);
    }

    /**
     * @param Set<Capability> $capabilities
     */
    public function canPerformAll(Set $capabilities): bool
    {
        return $this->possibleCapabilities->canPerformAll($capabilities);
    }
}
