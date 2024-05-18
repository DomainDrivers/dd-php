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
use DomainDrivers\SmartSchedule\Availability\ResourceId;
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
    private AllocatableResourceId $resourceId;
    private CapabilityScheduler $capabilityScheduler;

    #[\Override]
    protected function setUp(): void
    {
        $this->allocationFacade = self::getContainer()->get(AllocationFacade::class);
        $this->availabilityFacade = self::getContainer()->get(AvailabilityFacade::class);
        $this->capabilityScheduler = self::getContainer()->get(CapabilityScheduler::class);
        $this->resourceId = AllocatableResourceId::newOne();
    }

    #[Test]
    public function canAllocateCapabilityToProject(): void
    {
        // given
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $skillPhp = Capability::skill('php');
        $demand = new Demand($skillPhp, $oneDay);
        // and
        $allocatableCapabilityId = $this->createAllocatableResource($oneDay, $skillPhp, $this->resourceId);
        // and
        $projectId = ProjectAllocationsId::newOne();
        // and
        $this->allocationFacade->scheduleProjectAllocationDemands($projectId, Demands::of($demand));

        // when
        $result = $this->allocationFacade->allocateToProject($projectId, $allocatableCapabilityId, $skillPhp, $oneDay);

        // then
        self::assertTrue($result->isPresent());
        $summary = $this->allocationFacade->findAllProjectsAllocations();
        self::assertTrue($summary->projectAllocations->get($projectId->toString())->get()->all->equals(Set::of(new AllocatedCapability($allocatableCapabilityId, $skillPhp, $oneDay))));
        self::assertTrue($summary->demands->get($projectId->toString())->get()->all->equals(GenericList::of($demand)));
        self::assertTrue($this->availabilityWasBlocked($allocatableCapabilityId->toAvailabilityResourceId(), $oneDay, $projectId));
    }

    #[Test]
    public function cantAllocateWhenResourceNotAvailable(): void
    {
        // given
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $skillPhp = Capability::skill('php');
        $demand = new Demand($skillPhp, $oneDay);
        // and
        $allocatableCapabilityId = $this->createAllocatableResource($oneDay, $skillPhp, $this->resourceId);
        // and
        $this->availabilityFacade->block($allocatableCapabilityId->toAvailabilityResourceId(), $oneDay, Owner::newOne());
        // and
        $projectId = ProjectAllocationsId::newOne();
        // and
        $this->allocationFacade->scheduleProjectAllocationDemands($projectId, Demands::of($demand));

        // when
        $result = $this->allocationFacade->allocateToProject($projectId, $allocatableCapabilityId, $skillPhp, $oneDay);

        // then
        self::assertFalse($result->isPresent());
        $summary = $this->allocationFacade->findAllProjectsAllocations();
        self::assertTrue($summary->projectAllocations->get($projectId->toString())->get()->all->isEmpty());
    }

    #[Test]
    public function cantAllocateWhenCapabilityHasNotBeenScheduled(): void
    {
        // given
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $skillPhp = Capability::skill('php');
        $demand = new Demand($skillPhp, $oneDay);
        // and
        $notScheduledCapability = AllocatableCapabilityId::newOne();
        // and
        $projectId = ProjectAllocationsId::newOne();
        // and
        $this->allocationFacade->scheduleProjectAllocationDemands($projectId, Demands::of($demand));

        // when
        $result = $this->allocationFacade->allocateToProject($projectId, $notScheduledCapability, $skillPhp, $oneDay);

        // then
        self::assertFalse($result->isPresent());
        $summary = $this->allocationFacade->findAllProjectsAllocations();
        self::assertTrue($summary->projectAllocations->get($projectId->toString())->get()->all->isEmpty());
    }

    #[Test]
    public function canReleaseCapabilityFromProject(): void
    {
        // given
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        // and
        $allocatableCapabilityId = $this->createAllocatableResource($oneDay, Capability::skill('php'), $this->resourceId);
        // and
        $projectId = ProjectAllocationsId::newOne();
        // and
        $this->allocationFacade->scheduleProjectAllocationDemands($projectId, Demands::none());
        // and
        $chosenCapability = Capability::skill('php');
        $this->allocationFacade->allocateToProject($projectId, $allocatableCapabilityId, $chosenCapability, $oneDay);

        // when
        $result = $this->allocationFacade->releaseFromProject($projectId, $allocatableCapabilityId, $oneDay);

        // then
        self::assertTrue($result);
        $summary = $this->allocationFacade->findAllProjectsAllocations();
        self::assertTrue($summary->projectAllocations->get($projectId->toString())->get()->all->isEmpty());
        self::assertTrue($this->availabilityIsReleased($oneDay, $allocatableCapabilityId, $projectId));
    }

    private function createAllocatableResource(TimeSlot $period, Capability $capability, AllocatableResourceId $resourceId): AllocatableCapabilityId
    {
        $allocatableCapabilityIds = $this->capabilityScheduler->scheduleResourceCapabilitiesForPeriod($resourceId, GenericList::of(CapabilitySelector::canJustPerform($capability)), $period);
        \assert($allocatableCapabilityIds->length() === 1);

        return $allocatableCapabilityIds->get();
    }

    private function availabilityWasBlocked(ResourceId $resourceId, TimeSlot $period, ProjectAllocationsId $projectId): bool
    {
        return $this->availabilityFacade->loadCalendars(Set::of($resourceId), $period)
            ->calendars
            ->values()
            ->allMatch(fn (Calendar $c) => $c->takenBy(Owner::of($projectId->id))->equals(GenericList::of($period)));
    }

    private function availabilityIsReleased(TimeSlot $period, AllocatableCapabilityId $allocatableCapabilityId, ProjectAllocationsId $projectId): bool
    {
        return !$this->availabilityWasBlocked($allocatableCapabilityId->toAvailabilityResourceId(), $period, $projectId);
    }
}
