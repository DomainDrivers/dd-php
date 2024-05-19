<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\Calendar;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Availability\ResourceTakenOver;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[CoversClass(AvailabilityFacade::class)]
final class AvailabilityFacadeTest extends KernelTestCase
{
    use InteractsWithMessenger;

    private AvailabilityFacade $availabilityFacade;

    protected function setUp(): void
    {
        $this->availabilityFacade = self::getContainer()->get(AvailabilityFacade::class);
    }

    #[Test]
    public function canCreateAvailabilitySlots(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);

        // when
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);

        // then
        $entireMonth = TimeSlot::createMonthlyTimeSlotAtUTC(2021, 1);
        $monthlyCalendar = $this->availabilityFacade->loadCalendar($resourceId, $entireMonth);
        self::assertEquals(Calendar::withAvailableSlots($resourceId, $oneDay), $monthlyCalendar);
    }

    #[Test]
    public function canCreateNewAvailabilitySlotsWithParentId(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $resourceId2 = ResourceId::newOne();
        $parentId = ResourceId::newOne();
        $differentParentId = ResourceId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);

        // when
        $this->availabilityFacade->createResourceSlotsWitParent($resourceId, $parentId, $oneDay);
        $this->availabilityFacade->createResourceSlotsWitParent($resourceId2, $differentParentId, $oneDay);

        // then
        self::assertSame(96, $this->availabilityFacade->findByParentId($parentId, $oneDay)->size());
        self::assertSame(96, $this->availabilityFacade->findByParentId($differentParentId, $oneDay)->size());
    }

    #[Test]
    public function canBlockAvailabilities(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $owner = Owner::newOne();
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);

        // when
        $result = $this->availabilityFacade->block($resourceId, $oneDay, $owner);

        // then
        self::assertTrue($result);
        $entireMonth = TimeSlot::createMonthlyTimeSlotAtUTC(2021, 1);
        $monthlyCalendar = $this->availabilityFacade->loadCalendar($resourceId, $entireMonth);
        self::assertTrue($monthlyCalendar->availableSlots()->isEmpty());
        self::assertTrue($monthlyCalendar->takenBy($owner)->equals(GenericList::of($oneDay)));
    }

    #[Test]
    public function cantBlockWhenNoSlotsCreated(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $owner = Owner::newOne();

        // when
        $result = $this->availabilityFacade->block($resourceId, $oneDay, $owner);

        // then
        self::assertFalse($result);
    }

    #[Test]
    public function canDisableAvailabilities(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $owner = Owner::newOne();
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);

        // when
        $result = $this->availabilityFacade->disable($resourceId, $oneDay, $owner);

        // then
        self::assertTrue($result);
        $resourceAvailabilities = $this->availabilityFacade->find($resourceId, $oneDay);
        self::assertSame(96, $resourceAvailabilities->size());
        self::assertTrue($resourceAvailabilities->isDisabledEntirelyBy($owner));
    }

    #[Test]
    public function cantDisableWhenNoSlotsCreated(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $owner = Owner::newOne();

        // when
        $result = $this->availabilityFacade->disable($resourceId, $oneDay, $owner);

        // then
        self::assertFalse($result);
    }

    #[Test]
    public function cantBlockEvenWhenJustSmallSegmentOfRequestedSlotIsBlocked(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $owner = Owner::newOne();
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);
        // and
        $this->availabilityFacade->block($resourceId, $oneDay, $owner);
        $fifteenMinutes = new TimeSlot($oneDay->from, $oneDay->from->modify('+15 minutes'));

        // when
        $result = $this->availabilityFacade->block($resourceId, $fifteenMinutes, Owner::newOne());

        // then
        self::assertFalse($result);
        $resourceAvailabilities = $this->availabilityFacade->find($resourceId, $oneDay);
        self::assertTrue($resourceAvailabilities->blockedEntirelyBy($owner));
    }

    #[Test]
    public function canReleaseAvailability(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $owner = Owner::newOne();
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);
        // and
        $this->availabilityFacade->block($resourceId, $oneDay, $owner);

        // when
        $result = $this->availabilityFacade->release($resourceId, $oneDay, $owner);

        // then
        self::assertTrue($result);
        $resourceAvailabilities = $this->availabilityFacade->find($resourceId, $oneDay);
        self::assertTrue($resourceAvailabilities->isEntirelyAvailable());
    }

    #[Test]
    public function cantReleaseWhenNoSlotsCreated(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $owner = Owner::newOne();

        // when
        $result = $this->availabilityFacade->release($resourceId, $oneDay, $owner);

        // then
        self::assertFalse($result);
    }

    #[Test]
    public function cantReleaseEvenWhenJustPartOfSlotIsOwnedByTheRequester(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $jan_1 = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $jan_2 = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 2);
        $jan_1_2 = new TimeSlot($jan_1->from, $jan_2->to);
        $jan1owner = Owner::newOne();
        $this->availabilityFacade->createResourceSlots($resourceId, $jan_1_2);
        // and
        $this->availabilityFacade->block($resourceId, $jan_1, $jan1owner);
        // and
        $jan2owner = Owner::newOne();
        $this->availabilityFacade->block($resourceId, $jan_2, $jan2owner);

        // when
        $result = $this->availabilityFacade->release($resourceId, $jan_1_2, $jan1owner);

        // then
        self::assertFalse($result);
        $resourceAvailabilities = $this->availabilityFacade->find($resourceId, $jan_1);
        self::assertTrue($resourceAvailabilities->blockedEntirelyBy($jan1owner));
    }

    #[Test]
    public function oneSegmentCanBeTakenBySomeoneElseAfterRealising(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $fifteenMinutes = new TimeSlot($oneDay->from, $oneDay->from->modify('+15 minutes'));
        $owner = Owner::newOne();
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);
        // and
        $this->availabilityFacade->block($resourceId, $oneDay, $owner);
        // and
        $this->availabilityFacade->release($resourceId, $fifteenMinutes, $owner);

        // when
        $newOwner = Owner::newOne();
        $result = $this->availabilityFacade->block($resourceId, $fifteenMinutes, $newOwner);

        // then
        self::assertTrue($result);
        $dailyCalendar = $this->availabilityFacade->loadCalendar($resourceId, $oneDay);
        self::assertTrue($dailyCalendar->availableSlots()->isEmpty());
        self::assertTrue($dailyCalendar->takenBy($owner)->equals($oneDay->leftoverAfterRemovingCommonWith($fifteenMinutes)));
        self::assertTrue($dailyCalendar->takenBy($newOwner)->equals(GenericList::of($fifteenMinutes)));
    }

    #[Test]
    public function resourceTakenOverEventIsEmittedAfterTakingOverTheResource(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $initialOwner = Owner::newOne();
        $newOwner = Owner::newOne();
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);
        $this->availabilityFacade->block($resourceId, $oneDay, $initialOwner);

        // when
        $result = $this->availabilityFacade->disable($resourceId, $oneDay, $newOwner);

        // then
        self::assertTrue($result);
        $this->transport()->queue()
            ->assertCount(1)
            ->first(fn (ResourceTakenOver $event): bool => $event->resourceId->getId()->equals($resourceId->getId())
                && $event->previousOwners->equals(Set::of($initialOwner))
            )
        ;
    }
}
