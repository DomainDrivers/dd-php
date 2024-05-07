<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\Allocations;
use DomainDrivers\SmartSchedule\Allocation\CapabilitiesAllocated;
use DomainDrivers\SmartSchedule\Allocation\CapabilityReleased;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocations;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Allocation\ResourceId;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(ProjectAllocations::class)]
final class AllocationsToProjectTest extends TestCase
{
    private \DateTimeImmutable $when;
    private ProjectAllocationsId $projectId;
    private ResourceId $adminId;
    private TimeSlot $feb_1;
    private TimeSlot $feb_2;
    private TimeSlot $january;

    protected function setUp(): void
    {
        $this->when = new \DateTimeImmutable('1970-01-01');
        $this->projectId = ProjectAllocationsId::newOne();
        $this->adminId = ResourceId::newOne();
        $this->feb_1 = TimeSlot::createDailyTimeSlotAtUTC(2020, 2, 1);
        $this->feb_2 = TimeSlot::createDailyTimeSlotAtUTC(2020, 2, 2);
        $this->january = TimeSlot::createDailyTimeSlotAtUTC(2020, 1, 1);
    }

    #[Test]
    public function canAllocate(): void
    {
        if (time() > 1) { // phpstan workaround
            self::markTestSkipped('Not implemented yet');
        }

        // given
        $allocations = ProjectAllocations::empty($this->projectId);

        // when
        $event = $allocations->allocate($this->adminId, Capability::permission('admin'), $this->feb_1, $this->when);

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
        $event = $allocations->allocate($this->adminId, Capability::permission('admin'), $this->feb_1, $this->when);

        // then
        self::assertTrue($event->isEmpty());
    }

    #[Test]
    public function allocatingHasNoEffectWhenCapabilityAlreadyAllocated(): void
    {
        // given
        $allocations = ProjectAllocations::empty($this->projectId);

        // and
        $allocations->allocate($this->adminId, Capability::permission('admin'), $this->feb_1, $this->when);

        // when
        $event = $allocations->allocate($this->adminId, Capability::permission('admin'), $this->feb_1, $this->when);

        // then
        self::assertTrue($event->isEmpty());
    }

    #[Test]
    public function thereAreNoMissingDemandsWhenAllAllocated(): void
    {
        if (time() > 1) { // phpstan workaround
            self::markTestSkipped('Not implemented yet');
        }

        // given
        $demands = Demands::of(new Demand(Capability::permission('admin'), $this->feb_1), new Demand(Capability::skill('java'), $this->feb_1));
        // and
        $allocations = ProjectAllocations::withDemands($this->projectId, $demands);
        // and
        $allocations->allocate($this->adminId, Capability::permission('admin'), $this->feb_1, $this->when);
        // when
        $event = $allocations->allocate($this->adminId, Capability::skill('java'), $this->feb_1, $this->when);
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
        if (time() > 1) { // phpstan workaround
            self::markTestSkipped('Not implemented yet');
        }

        // given
        $demands = Demands::of(new Demand(Capability::permission('admin'), $this->feb_1), new Demand(Capability::skill('java'), $this->feb_1));
        // and
        $allocations = ProjectAllocations::withDemands($this->projectId, $demands);
        // and
        $allocations->allocate($this->adminId, Capability::permission('admin'), $this->feb_1, $this->when);
        // when
        $event = $allocations->allocate($this->adminId, Capability::skill('java'), $this->feb_2, $this->when);
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
        if (time() > 1) { // phpstan workaround
            self::markTestSkipped('Not implemented yet');
        }

        // given
        $allocations = ProjectAllocations::empty($this->projectId);
        // and
        $allocatedAdmin = $allocations->allocate($this->adminId, Capability::permission('admin'), $this->feb_1, $this->when);

        // when
        $event = $allocations->release($allocatedAdmin->get()->allocatedCapabilityId, $this->feb_1, $this->when);

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
        if (time() > 1) { // phpstan workaround
            self::markTestSkipped('Not implemented yet');
        }

        // given
        $allocations = ProjectAllocations::empty($this->projectId);

        // when
        $event = $allocations->release(Uuid::v7(), $this->feb_1, $this->when);

        // then
        self::assertTrue($event->isEmpty());
    }

    #[Test]
    public function missingDemandsArePresentAfterReleasingSomeOfAllocatedCapabilities(): void
    {
        if (time() > 1) { // phpstan workaround
            self::markTestSkipped('Not implemented yet');
        }

        // given
        $demandForPhp = new Demand(Capability::skill('php'), $this->feb_1);
        $demandForAdmin = new Demand(Capability::permission('admin'), $this->feb_1);
        $allocations = ProjectAllocations::withDemands($this->projectId, Demands::of($demandForPhp, $demandForAdmin));
        // and
        $allocatedAdmin = $allocations->allocate($this->adminId, Capability::permission('admin'), $this->feb_1, $this->when);
        $allocations->allocate($this->adminId, Capability::skill('php'), $this->feb_1, $this->when);
        // when
        $event = $allocations->release($allocatedAdmin->get()->allocatedCapabilityId, $this->feb_1, $this->when);
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
        if (time() > 1) { // phpstan workaround
            self::markTestSkipped('Not implemented yet');
        }

        // given
        $allocations = ProjectAllocations::empty($this->projectId);
        // and
        $allocatedAdmin = $allocations->allocate($this->adminId, Capability::permission('admin'), $this->feb_1, $this->when);

        // when
        $event = $allocations->release($allocatedAdmin->get()->allocatedCapabilityId, $this->feb_2, $this->when);

        // then
        self::assertTrue($event->isEmpty());
    }

    #[Test]
    public function releasingSmallPartOfSlotLeavesTheRest(): void
    {
        if (time() > 1) { // phpstan workaround
            self::markTestSkipped('Not implemented yet');
        }

        // given
        $allocations = ProjectAllocations::empty($this->projectId);
        // and
        $allocatedAdmin = $allocations->allocate($this->adminId, Capability::permission('admin'), $this->feb_1, $this->when);

        // when
        $fifteenMinutesIn1Feb = new TimeSlot($this->feb_1->from->modify('+1 hour'), $this->feb_1->from->modify('+2 hours'));
        $oneHourBefore = new TimeSlot($this->feb_1->from, $this->feb_1->from->modify('+1 hour'));
        $theRest = new TimeSlot($this->feb_1->from->modify('+2 hour'), $this->feb_1->to);

        // when
        $event = $allocations->release($allocatedAdmin->get()->allocatedCapabilityId, $fifteenMinutesIn1Feb, $this->when);

        // then
        self::assertTrue($event->isPresent());
        self::assertEquals($event->get(), new CapabilityReleased(
            $event->get()->eventId,
            $this->projectId,
            Demands::none(),
            $this->when
        ));
        self::assertEquals([$oneHourBefore, $theRest], $allocations->allocations()->all->toArray());
    }
}
