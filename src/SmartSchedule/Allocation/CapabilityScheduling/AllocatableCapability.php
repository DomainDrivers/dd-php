<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;

#[Entity]
#[Table('allocatable_capabilities')]
final class AllocatableCapability
{
    #[Id]
    #[Column(type: 'allocatable_capability_id')]
    private AllocatableCapabilityId $id;

    #[Column(type: 'capability', options: ['jsonb' => true])]
    private Capability $capability;

    #[Column(type: 'allocatable_resource_id')]
    private AllocatableResourceId $resourceId;

    #[Embedded(class: TimeSlot::class, columnPrefix: 'date_')]
    private TimeSlot $timeSlot;

    public function __construct(AllocatableResourceId $resourceId, Capability $capability, TimeSlot $timeSlot)
    {
        $this->id = AllocatableCapabilityId::newOne();
        $this->capability = $capability;
        $this->resourceId = $resourceId;
        $this->timeSlot = $timeSlot;
    }

    public function id(): AllocatableCapabilityId
    {
        return $this->id;
    }

    public function capability(): Capability
    {
        return $this->capability;
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
        return $this->capability->equals($capability);
    }
}
