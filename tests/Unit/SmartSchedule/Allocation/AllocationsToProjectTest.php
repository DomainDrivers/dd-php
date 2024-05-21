<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\AllocatedCapability;
use DomainDrivers\SmartSchedule\Allocation\Allocations;
use DomainDrivers\SmartSchedule\Allocation\CapabilitiesAllocated;
use DomainDrivers\SmartSchedule\Allocation\CapabilityReleased;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocations;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationScheduled;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsDemandsScheduled;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProjectAllocations::class)]
final class AllocationsToProjectTest extends TestCase
{
    private \DateTimeImmutable $when;
    private ProjectAllocationsId $projectId;
    private AllocatableCapabilityId $adminId;
    private TimeSlot $feb_1;
    private TimeSlot $feb_2;
    private TimeSlot $january;
    private TimeSlot $february;

    #[\Override]
    protected function setUp(): void
    {
        $this->when = new \DateTimeImmutable('1970-01-01');
        $this->projectId = ProjectAllocationsId::newOne();
        $this->adminId = AllocatableCapabilityId::newOne();
        $this->feb_1 = TimeSlot::createDailyTimeSlotAtUTC(2020, 2, 1);
        $this->feb_2 = TimeSlot::createDailyTimeSlotAtUTC(2020, 2, 2);
        $this->january = TimeSlot::createMonthlyTimeSlotAtUTC(2020, 1);
        $this->february = TimeSlot::createMonthlyTimeSlotAtUTC(2020, 2);
    }

    #[Test]
    public function canAllocate(): void
    {
        // given
        $allocations = ProjectAllocations::empty($this->projectId);

        // when
        $event = $allocations->allocate($this->adminId, CapabilitySelector::canJustPerform(Capability::permission('admin')), $this->feb_1, $this->when);

        // then
        self::assertTrue($event->isPresent());
        $capabilitiesAllocated = $event->get();
        self::assertEquals($event->get(), new CapabilitiesAllocated(
            $capabilitiesAllocated->eventId,
            $capabilitiesAllocated->allocatedCapabilityId,
            $this->projectId,
            Demands::none(),
            $this->when
        ));
    }

    #[Test]
    public function cantAllocateWhenRequestedTimeSlotNotWithingProjectSlot(): void
    {
        // given
        $allocations = new ProjectAllocations($this->projectId, Allocations::none(), Demands::none(), $this->january);

        // when
        $event = $allocations->allocate($this->adminId, CapabilitySelector::canJustPerform(Capability::permission('admin')), $this->feb_1, $this->when);

        // then
        self::assertTrue($event->isEmpty());
    }

    #[Test]
    public function allocatingHasNoEffectWhenCapabilityAlreadyAllocated(): void
    {
        // given
        $allocations = ProjectAllocations::empty($this->projectId);

        // and
        $allocations->allocate($this->adminId, CapabilitySelector::canJustPerform(Capability::permission('admin')), $this->feb_1, $this->when);

        // when
        $event = $allocations->allocate($this->adminId, CapabilitySelector::canJustPerform(Capability::permission('admin')), $this->feb_1, $this->when);

        // then
        self::assertTrue($event->isEmpty());
    }

    #[Test]
    public function thereAreNoMissingDemandsWhenAllAllocated(): void
    {
        // given
        $demands = Demands::of(new Demand(Capability::permission('admin'), $this->feb_1), new Demand(Capability::skill('java'), $this->feb_1));
        // and
        $allocations = ProjectAllocations::withDemands($this->projectId, $demands);
        // and
        $allocations->allocate($this->adminId, CapabilitySelector::canJustPerform(Capability::permission('admin')), $this->feb_1, $this->when);
        // when
        $event = $allocations->allocate($this->adminId, CapabilitySelector::canJustPerform(Capability::skill('java')), $this->feb_1, $this->when);
        // then
        self::assertTrue($event->isPresent());
        $capabilitiesAllocated = $event->get();
        self::assertEquals($event->get(), new CapabilitiesAllocated(
            $capabilitiesAllocated->eventId,
            $capabilitiesAllocated->allocatedCapabilityId,
            $this->projectId,
            Demands::none(),
            $this->when
        ));
    }

    #[Test]
    public function missingDemandsArePresentWhenAllocatingForDifferentThanDemandedSlot(): void
    {
        // given
        $demands = Demands::of(new Demand(Capability::permission('admin'), $this->feb_1), new Demand(Capability::skill('java'), $this->feb_1));
        // and
        $allocations = ProjectAllocations::withDemands($this->projectId, $demands);
        // and
        $allocations->allocate($this->adminId, CapabilitySelector::canJustPerform(Capability::permission('admin')), $this->feb_1, $this->when);
        // when
        $event = $allocations->allocate($this->adminId, CapabilitySelector::canJustPerform(Capability::skill('java')), $this->feb_2, $this->when);
        // then
        self::assertTrue($event->isPresent());
        self::assertTrue($allocations->missingDemands()->all->equals(GenericList::of(new Demand(Capability::skill('java'), $this->feb_1))));
        $capabilitiesAllocated = $event->get();
        self::assertEquals($event->get(), new CapabilitiesAllocated(
            $capabilitiesAllocated->eventId,
            $capabilitiesAllocated->allocatedCapabilityId,
            $this->projectId,
            Demands::of(new Demand(Capability::skill('java'), $this->feb_1)),
            $this->when
        ));
    }

    #[Test]
    public function canRelease(): void
    {
        // given
        $allocations = ProjectAllocations::empty($this->projectId);
        // and
        $allocatedAdmin = $allocations->allocate($this->adminId, CapabilitySelector::canJustPerform(Capability::permission('admin')), $this->feb_1, $this->when);

        // when
        $adminId = new AllocatableCapabilityId($allocatedAdmin->get()->allocatedCapabilityId);
        $event = $allocations->release($adminId, $this->feb_1, $this->when);

        // then
        self::assertTrue($event->isPresent());
        self::assertEquals($event->get(), new CapabilityReleased(
            $event->get()->eventId,
            $this->projectId,
            Demands::none(),
            $this->when
        ));
    }

    #[Test]
    public function releasingHasNoEffectWhenCapabilityWasNotAllocated(): void
    {
        // given
        $allocations = ProjectAllocations::empty($this->projectId);

        // when
        $event = $allocations->release(AllocatableCapabilityId::newOne(), $this->feb_1, $this->when);

        // then
        self::assertTrue($event->isEmpty());
    }

    #[Test]
    public function missingDemandsArePresentAfterReleasingSomeOfAllocatedCapabilities(): void
    {
        // given
        $demandForPhp = new Demand(Capability::skill('php'), $this->feb_1);
        $demandForAdmin = new Demand(Capability::permission('admin'), $this->feb_1);
        $allocations = ProjectAllocations::withDemands($this->projectId, Demands::of($demandForPhp, $demandForAdmin));
        // and
        $allocatedAdmin = $allocations->allocate($this->adminId, CapabilitySelector::canJustPerform(Capability::permission('admin')), $this->feb_1, $this->when);
        $allocations->allocate(AllocatableCapabilityId::newOne(), CapabilitySelector::canJustPerform(Capability::skill('php')), $this->feb_1, $this->when);
        // when
        $event = $allocations->release(new AllocatableCapabilityId($allocatedAdmin->get()->allocatedCapabilityId), $this->feb_1, $this->when);
        // then
        self::assertTrue($event->isPresent());
        self::assertEquals($event->get(), new CapabilityReleased(
            $event->get()->eventId,
            $this->projectId,
            Demands::of($demandForAdmin),
            $this->when
        ));
    }

    #[Test]
    public function releasingHasNoEffectWhenReleasingSlotNotWithinAllocatedSlot(): void
    {
        // given
        $allocations = ProjectAllocations::empty($this->projectId);
        // and
        $allocatedAdmin = $allocations->allocate($this->adminId, CapabilitySelector::canJustPerform(Capability::permission('admin')), $this->feb_1, $this->when);

        // when
        $event = $allocations->release(new AllocatableCapabilityId($allocatedAdmin->get()->allocatedCapabilityId), $this->feb_2, $this->when);

        // then
        self::assertTrue($event->isEmpty());
    }

    #[Test]
    public function releasingSmallPartOfSlotLeavesTheRest(): void
    {
        // given
        $allocations = ProjectAllocations::empty($this->projectId);
        // and
        $allocatedAdmin = $allocations->allocate($this->adminId, CapabilitySelector::canJustPerform(Capability::permission('admin')), $this->feb_1, $this->when);

        // when
        $fifteenMinutesIn1Feb = new TimeSlot($this->feb_1->from->modify('+1 hour'), $this->feb_1->from->modify('+2 hours'));
        $oneHourBefore = new TimeSlot($this->feb_1->from, $this->feb_1->from->modify('+1 hour'));
        $theRest = new TimeSlot($this->feb_1->from->modify('+2 hour'), $this->feb_1->to);

        // when
        $event = $allocations->release(new AllocatableCapabilityId($allocatedAdmin->get()->allocatedCapabilityId), $fifteenMinutesIn1Feb, $this->when);

        // then
        self::assertTrue($event->isPresent());
        self::assertEquals($event->get(), new CapabilityReleased(
            $event->get()->eventId,
            $this->projectId,
            Demands::none(),
            $this->when
        ));
        self::assertTrue($allocations->allocations()->all->equals(Set::of(
            new AllocatedCapability($this->adminId, CapabilitySelector::canJustPerform(Capability::permission('admin')), $oneHourBefore),
            new AllocatedCapability($this->adminId, CapabilitySelector::canJustPerform(Capability::permission('admin')), $theRest)
        )));
    }

    #[Test]
    public function canChangeDemands(): void
    {
        // given
        $demands = Demands::of(new Demand(Capability::permission('admin'), $this->feb_1), new Demand(Capability::skill('php'), $this->feb_1));
        // and
        $allocations = ProjectAllocations::withDemands($this->projectId, $demands);
        // and
        $allocations->allocate($this->adminId, CapabilitySelector::canJustPerform(Capability::permission('admin')), $this->feb_1, $this->when);
        // when
        $event = $allocations->addDemands(Demands::of(new Demand(Capability::skill('python'), $this->feb_1)), $this->when);
        // then
        self::assertTrue($allocations->missingDemands()->all->equals(Demands::allInSameTimeSlot($this->feb_1, Capability::skill('php'), Capability::skill('python'))->all));
        self::assertEquals($event->get(), new ProjectAllocationsDemandsScheduled(
            $event->get()->uuid,
            $this->projectId,
            Demands::allInSameTimeSlot($this->feb_1, Capability::skill('php'), Capability::skill('python')),
            $this->when
        ));
    }

    #[Test]
    public function canChangeProjectDates(): void
    {
        // given
        $allocations = new ProjectAllocations($this->projectId, Allocations::none(), Demands::none(), $this->january);

        // when
        $event = $allocations->defineSlot($this->february, $this->when);

        // then
        self::assertTrue($event->isPresent());
        self::assertEquals($event->get(), new ProjectAllocationScheduled(
            $event->get()->uuid,
            $this->projectId,
            $this->february,
            $this->when
        ));
    }
}
