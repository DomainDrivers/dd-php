<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailabilityId;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AvailabilityFacade::class)]
final class AvailabilityFacadeTest extends TestCase
{
    private AvailabilityFacade $availabilityFacade;

    protected function setUp(): void
    {
        $this->availabilityFacade = new AvailabilityFacade();
    }

    #[Test]
    public function canCreateAvailabilitySlots(): void
    {
        // given
        $resourceId = ResourceAvailabilityId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);

        // when
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);

        // then
        $this->expectNotToPerformAssertions();
        // todo check that availability(ies) was/were created
    }

    #[Test]
    public function canBlockAvailabilities(): void
    {
        // given
        $resourceId = ResourceAvailabilityId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $owner = Owner::newOne();
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);

        // when
        $result = $this->availabilityFacade->block($resourceId, $oneDay, $owner);

        // then
        self::assertTrue($result);
        // todo check that can't be taken
    }

    #[Test]
    public function canDisableAvailabilities(): void
    {
        // given
        $resourceId = ResourceAvailabilityId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $owner = Owner::newOne();
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);

        // when
        $result = $this->availabilityFacade->disable($resourceId, $oneDay, $owner);

        // then
        self::assertTrue($result);
        // todo check that are disabled
    }

    #[Test]
    public function cantBlockEvenWhenJustSmallSegmentOfRequestedSlotIsBlocked(): void
    {
        // given
        $resourceId = ResourceAvailabilityId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $owner = Owner::newOne();
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);
        // and
        $this->availabilityFacade->block($resourceId, $oneDay, $owner);
        $fifteenMinutes = new TimeSlot($oneDay->from, $oneDay->from->modify('+15 minutes'));

        // when
        $result = $this->availabilityFacade->block($resourceId, $fifteenMinutes, Owner::newOne());

        // then
        self::assertTrue($result);
        // todo check that nothing was changed
    }

    #[Test]
    public function canReleaseAvailability(): void
    {
        // given
        $resourceId = ResourceAvailabilityId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $owner = Owner::newOne();
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);
        // and
        $this->availabilityFacade->block($resourceId, $oneDay, $owner);

        // when
        $result = $this->availabilityFacade->release($resourceId, $oneDay, $owner);

        // then
        self::assertTrue($result);
        // todo check can be taken again
    }

    #[Test]
    public function cantReleaseEvenWhenJustPartOfSlotIsOwnedByTheRequester(): void
    {
        // given
        $resourceId = ResourceAvailabilityId::newOne();
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
        self::assertTrue($result);
        // todo check still owned by jan1
    }
}
