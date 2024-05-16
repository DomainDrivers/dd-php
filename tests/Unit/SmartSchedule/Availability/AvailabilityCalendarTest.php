<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(AvailabilityFacade::class)]
final class AvailabilityCalendarTest extends KernelTestCase
{
    private AvailabilityFacade $availabilityFacade;

    protected function setUp(): void
    {
        $this->availabilityFacade = self::getContainer()->get(AvailabilityFacade::class);
    }

    #[Test]
    public function loadsCalendarForEntireMonth(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $fifteenMinutes = new TimeSlot($oneDay->from->modify('+15 minutes'), $oneDay->from->modify('+30 minutes'));
        $owner = Owner::newOne();
        // and
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);

        // when
        $this->availabilityFacade->block($resourceId, $fifteenMinutes, $owner);

        // then
        $calendar = $this->availabilityFacade->loadCalendar($resourceId, $oneDay);
        self::assertTrue($calendar->takenBy($owner)->equals(GenericList::of($fifteenMinutes)));
        self::assertTrue($calendar->availableSlots()->equals($oneDay->leftoverAfterRemovingCommonWith($fifteenMinutes)));
    }

    #[Test]
    public function loadsCalendarForMultipleResources(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $resourceId2 = ResourceId::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $fifteenMinutes = new TimeSlot($oneDay->from->modify('+15 minutes'), $oneDay->from->modify('+30 minutes'));

        $owner = Owner::newOne();
        $this->availabilityFacade->createResourceSlots($resourceId, $oneDay);
        $this->availabilityFacade->createResourceSlots($resourceId2, $oneDay);

        // when
        $this->availabilityFacade->block($resourceId, $fifteenMinutes, $owner);
        $this->availabilityFacade->block($resourceId2, $fifteenMinutes, $owner);

        // then
        $calendars = $this->availabilityFacade->loadCalendars(Set::of($resourceId, $resourceId2), $oneDay);
        self::assertTrue($calendars->get($resourceId)->takenBy($owner)->equals(GenericList::of($fifteenMinutes)));
        self::assertTrue($calendars->get($resourceId2)->takenBy($owner)->equals(GenericList::of($fifteenMinutes)));
        self::assertTrue($calendars->get($resourceId)->availableSlots()->equals($oneDay->leftoverAfterRemovingCommonWith($fifteenMinutes)));
        self::assertTrue($calendars->get($resourceId2)->availableSlots()->equals($oneDay->leftoverAfterRemovingCommonWith($fifteenMinutes)));
    }
}
