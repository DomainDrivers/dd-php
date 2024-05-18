<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation\CapabilityScheduling;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilitySummary;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableResourceId;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityFinder;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityScheduler;
use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(CapabilityScheduler::class)]
#[CoversClass(CapabilityFinder::class)]
final class CapabilitySchedulingTest extends KernelTestCase
{
    private CapabilityScheduler $capabilityScheduler;
    private CapabilityFinder $capabilityFinder;
    private AvailabilityFacade $availabilityFacade;

    #[\Override]
    protected function setUp(): void
    {
        $this->capabilityScheduler = self::getContainer()->get(CapabilityScheduler::class);
        $this->capabilityFinder = self::getContainer()->get(CapabilityFinder::class);
        $this->availabilityFacade = self::getContainer()->get(AvailabilityFacade::class);
    }

    #[Test]
    public function canScheduleAllocatableCapabilities(): void
    {
        // given
        $phpSkill = Capability::skill('php');
        $rustSkill = Capability::skill('rust');
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);

        // when
        $allocatable = $this->capabilityScheduler->scheduleResourceCapabilitiesForPeriod(AllocatableResourceId::newOne(), GenericList::of($phpSkill, $rustSkill), $oneDay);

        // then
        $loaded = $this->capabilityFinder->findById($allocatable);
        self::assertSame($allocatable->length(), $loaded->all->length());
        self::assertTrue($loaded->all->allMatch(fn (AllocatableCapabilitySummary $acs) => $this->availabilitySlotsAreCreated($acs, $oneDay)));
    }

    #[Test]
    public function capabilityIsFoundWhenCapabilityPresentInTimeSlot(): void
    {
        // given
        $uniqueSkill = Capability::skill('FITNESS-CLASS');
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $anotherDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 2);
        // and
        $this->capabilityScheduler->scheduleResourceCapabilitiesForPeriod(AllocatableResourceId::newOne(), GenericList::of($uniqueSkill), $oneDay);

        // when
        $found = $this->capabilityFinder->findAvailableCapabilities($uniqueSkill, $oneDay);
        $notFound = $this->capabilityFinder->findAvailableCapabilities($uniqueSkill, $anotherDay);

        // then
        self::assertSame(1, $found->all->length());
        self::assertTrue($notFound->all->isEmpty());
        self::assertTrue($found->all->get()->capability->equals($uniqueSkill));
        self::assertTrue($found->all->get()->timeSlot->equals($oneDay));
    }

    #[Test]
    public function capabilityNotFoundWhenCapabilityNotPresent(): void
    {
        // given
        $admin = Capability::permission('admin');
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        // and
        $this->capabilityScheduler->scheduleResourceCapabilitiesForPeriod(AllocatableResourceId::newOne(), GenericList::of($admin), $oneDay);

        // when
        $rust = Capability::skill('RUST JUST FOR NINJAS');
        $found = $this->capabilityFinder->findAvailableCapabilities($rust, $oneDay);

        // then
        self::assertTrue($found->all->isEmpty());
    }

    #[Test]
    public function canScheduleMultipleCapabilitiesOfSameType(): void
    {
        // given
        $loading = Capability::permission('LOADING_TRUCK');
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        // and
        $truck1 = AllocatableResourceId::newOne();
        $truck2 = AllocatableResourceId::newOne();
        $truck3 = AllocatableResourceId::newOne();
        $this->capabilityScheduler->scheduleMultipleResourcesForPeriod(Set::of($truck1, $truck2, $truck3), $loading, $oneDay);

        // when
        $found = $this->capabilityFinder->findCapabilities($loading, $oneDay);

        // then
        self::assertSame(3, $found->all->length());
    }

    #[Test]
    public function canFindCapabilityIgnoringAvailability(): void
    {
        // given
        $admin = Capability::permission('REALLY_UNIQUE_ADMIN');
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $differentDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 2, 1);
        $hourWithinDay = new TimeSlot($oneDay->from, $oneDay->from->modify('+1 hour'));
        $partiallyOverlappingDay = new TimeSlot($oneDay->from->modify('+1 hour'), $oneDay->to->modify('+1 hour'));
        // and
        $this->capabilityScheduler->scheduleResourceCapabilitiesForPeriod(AllocatableResourceId::newOne(), GenericList::of($admin), $oneDay);

        // when
        $onTheExactDay = $this->capabilityFinder->findCapabilities($admin, $oneDay);
        $onDifferentDay = $this->capabilityFinder->findCapabilities($admin, $differentDay);
        $inSlotWithin = $this->capabilityFinder->findCapabilities($admin, $hourWithinDay);
        $inOverlappingSlot = $this->capabilityFinder->findCapabilities($admin, $partiallyOverlappingDay);

        // then
        self::assertSame(1, $onTheExactDay->all->length());
        self::assertSame(1, $inSlotWithin->all->length());
        self::assertTrue($onDifferentDay->all->isEmpty());
        self::assertTrue($inOverlappingSlot->all->isEmpty());
    }

    private function availabilitySlotsAreCreated(AllocatableCapabilitySummary $allocatableCapability, TimeSlot $oneDay): bool
    {
        return $this->availabilityFacade->loadCalendar($allocatableCapability->id->toAvailabilityResourceId(), $oneDay)->availableSlots()->equals(GenericList::of($oneDay));
    }
}
