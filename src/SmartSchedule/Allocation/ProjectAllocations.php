<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use Doctrine\ORM\Mapping\Entity;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Control\Option;
use Symfony\Component\Uid\Uuid;

#[Entity]
final class ProjectAllocations
{
    private ProjectAllocationsId $projectId;

    private Allocations $allocations;

    private Demands $demands;

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
    public function allocate(ResourceId $resourceId, Capability $capability, TimeSlot $requestedSlot, \DateTimeImmutable $when): Option
    {
        if ($this->nothingAllocated() || !$this->withinProjectTimeSlot($requestedSlot)) {
            /** @var Option<CapabilitiesAllocated> $none */
            $none = Option::none();

            return $none;
        }

        return Option::of(CapabilitiesAllocated::new(Uuid::v7(), $this->projectId, Demands::none(), $when));
    }

    private function nothingAllocated(): bool
    {
        return false;
    }

    private function withinProjectTimeSlot(TimeSlot $requestedSlot): bool
    {
        return false;
    }

    /**
     * @return Option<CapabilityReleased>
     */
    public function release(Uuid $allocatedCapabilityId, TimeSlot $timeSlot, \DateTimeImmutable $when): Option
    {
        if ($this->nothingReleased()) {
            /** @var Option<CapabilityReleased> $none */
            $none = Option::none();

            return $none;
        }

        return Option::of(CapabilityReleased::new($this->projectId, Demands::none(), $when));
    }

    private function nothingReleased(): bool
    {
        return false;
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
}
