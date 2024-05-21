<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\AllocatedCapability;
use DomainDrivers\SmartSchedule\Allocation\AllocationFacade;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableResourceId;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityScheduler;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\Calendar;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(AllocationFacade::class)]
final class CapabilityAllocatingTest extends KernelTestCase
{
    private AllocationFacade $allocationFacade;
    private AvailabilityFacade $availabilityFacade;
    private CapabilityScheduler $capabilityScheduler;
    private AllocatableResourceId $allocatableResourceId;
    private AllocatableResourceId $allocatableResourceId2;
    private AllocatableResourceId $allocatableResourceId3;

    #[\Override]
    protected function setUp(): void
    {
        $this->allocationFacade = self::getContainer()->get(AllocationFacade::class);
        $this->availabilityFacade = self::getContainer()->get(AvailabilityFacade::class);
        $this->capabilityScheduler = self::getContainer()->get(CapabilityScheduler::class);
        $this->allocatableResourceId = AllocatableResourceId::newOne();
        $this->allocatableResourceId2 = AllocatableResourceId::newOne();
        $this->allocatableResourceId3 = AllocatableResourceId::newOne();
    }

    #[Test]
    public function canAllocateAnyCapabilityOfRequiredType(): void
    {
        // given
        $phpAndPython = CapabilitySelector::canPerformOneOf(Capability::skills('php', 'python'));
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        // and
        $allocatableCapabilityId1 = $this->scheduleCapabilities($this->allocatableResourceId, $phpAndPython, $oneDay);
        $allocatableCapabilityId2 = $this->scheduleCapabilities($this->allocatableResourceId2, $phpAndPython, $oneDay);
        $allocatableCapabilityId3 = $this->scheduleCapabilities($this->allocatableResourceId3, $phpAndPython, $oneDay);
        // and
        $projectId = ProjectAllocationsId::newOne();
        $this->allocationFacade->scheduleProjectAllocationDemands($projectId, Demands::none());

        // when
        $result = $this->allocationFacade->allocateCapabilityToProjectForPeriod($projectId, Capability::skill('php'), $oneDay);

        // then
        self::assertTrue($result);
        $allocatedCapabilities = $this->loadProjectAllocations($projectId);
        self::assertTrue($allocatedCapabilities->contains($allocatableCapabilityId1) || $allocatedCapabilities->contains($allocatableCapabilityId2) || $allocatedCapabilities->contains($allocatableCapabilityId3));
        self::assertTrue($this->availabilityWasBlocked($allocatedCapabilities, $oneDay, $projectId));
    }

    #[Test]
    public function cantAllocateAnyCapabilityOfRequiredTypeWhenNoCapabilities(): void
    {
        // given
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        // and
        $projectId = ProjectAllocationsId::newOne();
        // and
        $this->allocationFacade->scheduleProjectAllocationDemands($projectId, Demands::none());

        // when
        $result = $this->allocationFacade->allocateCapabilityToProjectForPeriod($projectId, Capability::skill('DEBUGGING'), $oneDay);

        // then
        self::assertFalse($result);
        $summary = $this->allocationFacade->findAllProjectsAllocations();
        self::assertTrue($summary->projectAllocations->get((string) $projectId)->get()->all->isEmpty());
    }

    #[Test]
    public function cantAllocateAnyCapabilityOfRequiredTypeWhenAllCapabilitiesTaken(): void
    {
        // given
        $capability = CapabilitySelector::canPerformOneOf(Capability::skills('DEBUGGING'));
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);

        $allocatableCapabilityId1 = $this->scheduleCapabilities($this->allocatableResourceId, $capability, $oneDay);
        $allocatableCapabilityId2 = $this->scheduleCapabilities($this->allocatableResourceId2, $capability, $oneDay);
        // and
        $project1 = $this->allocationFacade->createAllocation($oneDay, Demands::of(new Demand(Capability::skill('DEBUGGING'), $oneDay)));
        $project2 = $this->allocationFacade->createAllocation($oneDay, Demands::of(new Demand(Capability::skill('DEBUGGING'), $oneDay)));
        // and
        $this->allocationFacade->allocateToProject($project1, $allocatableCapabilityId1, $oneDay);
        $this->allocationFacade->allocateToProject($project2, $allocatableCapabilityId2, $oneDay);
        // and
        $projectId = ProjectAllocationsId::newOne();
        $this->allocationFacade->scheduleProjectAllocationDemands($projectId, Demands::none());

        // when
        $result = $this->allocationFacade->allocateCapabilityToProjectForPeriod($projectId, Capability::skill('DEBUGGING'), $oneDay);

        // then
        self::assertFalse($result);
        $summary = $this->allocationFacade->findAllProjectsAllocations();
        self::assertTrue($summary->projectAllocations->get((string) $projectId)->get()->all->isEmpty());
    }

    /**
     * @return Set<AllocatableCapabilityId>
     */
    private function loadProjectAllocations(ProjectAllocationsId $projectId): Set
    {
        return $this->allocationFacade->findAllProjectsAllocations()
            ->projectAllocations
            ->get((string) $projectId)
            ->get()
            ->all
            ->map(fn (AllocatedCapability $ac) => $ac->allocatedCapabilityID);
    }

    /**
     * @param Set<AllocatableCapabilityId> $capabilities
     */
    private function availabilityWasBlocked(Set $capabilities, TimeSlot $period, ProjectAllocationsId $projectId): bool
    {
        return $this->availabilityFacade->loadCalendars($capabilities->map(fn (AllocatableCapabilityId $id) => $id->toAvailabilityResourceId()), $period)
            ->calendars
            ->values()
            ->allMatch(fn (Calendar $c) => $c->takenBy(Owner::of($projectId->id))->equals(GenericList::of($period)));
    }

    private function scheduleCapabilities(AllocatableResourceId $resourceId, CapabilitySelector $capabilities, TimeSlot $period): AllocatableCapabilityId
    {
        $allocatableCapabilityIds = $this->capabilityScheduler->scheduleResourceCapabilitiesForPeriod($resourceId, GenericList::of($capabilities), $period);
        \assert($allocatableCapabilityIds->length() === 1);

        return $allocatableCapabilityIds->get();
    }
}
