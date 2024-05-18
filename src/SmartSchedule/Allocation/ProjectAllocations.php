<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Control\Option;

#[Entity]
#[Table(name: 'project_allocations')]
final class ProjectAllocations
{
    #[Id]
    #[Column(type: 'project_allocations_id')]
    private ProjectAllocationsId $projectId;

    #[Column(type: 'allocations', options: ['jsonb' => true])]
    private Allocations $allocations;

    #[Column(type: 'allocation_demands', options: ['jsonb' => true])]
    private Demands $demands;

    #[Embedded(class: TimeSlot::class, columnPrefix: 'date_')]
    private TimeSlot $timeSlot;

    public function __construct(
        ProjectAllocationsId $projectId,
        Allocations $allocations,
        Demands $demands,
        TimeSlot $timeSlot
    ) {
        $this->projectId = $projectId;
        $this->allocations = $allocations;
        $this->demands = $demands;
        $this->timeSlot = $timeSlot;
    }

    public static function empty(ProjectAllocationsId $projectId): self
    {
        return new self($projectId, Allocations::none(), Demands::none(), TimeSlot::empty());
    }

    public static function withDemands(ProjectAllocationsId $projectId, Demands $demands): self
    {
        return new self($projectId, Allocations::none(), $demands, TimeSlot::empty());
    }

    /**
     * @return Option<CapabilitiesAllocated>
     */
    public function allocate(AllocatableCapabilityId $allocatableCapabilityId, Capability $capability, TimeSlot $requestedSlot, \DateTimeImmutable $when): Option
    {
        $allocatedCapability = new AllocatedCapability($allocatableCapabilityId, $capability, $requestedSlot);
        $newAllocations = $this->allocations->add($allocatedCapability);
        if ($this->nothingAllocated($newAllocations) || !$this->withinProjectTimeSlot($requestedSlot)) {
            /** @var Option<CapabilitiesAllocated> $none */
            $none = Option::none();

            return $none;
        }

        $this->allocations = $newAllocations;

        return Option::of(CapabilitiesAllocated::new($allocatedCapability->allocatedCapabilityID->id, $this->projectId, $this->missingDemands(), $when));
    }

    private function nothingAllocated(Allocations $newAllocations): bool
    {
        return $newAllocations->all->equals($this->allocations->all);
    }

    private function withinProjectTimeSlot(TimeSlot $requestedSlot): bool
    {
        if (!$this->hasTimeSlot()) {
            return true;
        }

        return $requestedSlot->within($this->timeSlot);
    }

    /**
     * @return Option<CapabilityReleased>
     */
    public function release(AllocatableCapabilityId $allocatedCapabilityId, TimeSlot $timeSlot, \DateTimeImmutable $when): Option
    {
        $newAllocations = $this->allocations->remove($allocatedCapabilityId, $timeSlot);
        if ($this->nothingReleased($newAllocations)) {
            /** @var Option<CapabilityReleased> $none */
            $none = Option::none();

            return $none;
        }
        $this->allocations = $newAllocations;

        return Option::of(CapabilityReleased::new($this->projectId, $this->missingDemands(), $when));
    }

    private function nothingReleased(Allocations $newAllocations): bool
    {
        return $newAllocations->all->equals($this->allocations->all);
    }

    public function missingDemands(): Demands
    {
        return $this->demands->missingDemands($this->allocations);
    }

    public function allocations(): Allocations
    {
        return $this->allocations;
    }

    public function hasTimeSlot(): bool
    {
        return !$this->timeSlot->isEmpty();
    }

    /**
     * @return Option<ProjectAllocationScheduled>
     */
    public function defineSlot(TimeSlot $timeSlot, \DateTimeImmutable $when): Option
    {
        $this->timeSlot = $timeSlot;

        return Option::of(ProjectAllocationScheduled::new($this->projectId, $this->timeSlot, $when));
    }

    /**
     * @return Option<ProjectAllocationsDemandsScheduled>
     */
    public function addDemands(Demands $demands, \DateTimeImmutable $when): Option
    {
        $this->demands = $this->demands->withNew($demands);

        return Option::of(ProjectAllocationsDemandsScheduled::new($this->projectId, $this->missingDemands(), $when));
    }

    public function id(): ProjectAllocationsId
    {
        return $this->projectId;
    }

    public function demands(): Demands
    {
        return $this->demands;
    }

    public function timeSlot(): TimeSlot
    {
        return $this->timeSlot;
    }
}
