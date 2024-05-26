<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Availability\Segment\Segments;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
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
        $durationOfSevenSlots = Duration::ofMinutes(7 * Segments::DEFAULT_SEGMENT_DURATION_IN_MINUTES);
        $sevenSlots = TimeSlot::createTimeSlotAtUTCOfDuration(2021, 1, 1, $durationOfSevenSlots);
        $minimumSlot = new TimeSlot($sevenSlots->from, $sevenSlots->from->modify(sprintf('+%s minutes', Segments::DEFAULT_SEGMENT_DURATION_IN_MINUTES)));
        $owner = Owner::newOne();
        // and
        $this->availabilityFacade->createResourceSlots($resourceId, $sevenSlots);

        // when
        $this->availabilityFacade->block($resourceId, $minimumSlot, $owner);

        // then
        $calendar = $this->availabilityFacade->loadCalendar($resourceId, $sevenSlots);
        self::assertTrue($calendar->takenBy($owner)->equals(GenericList::of($minimumSlot)));
        self::assertTrue($calendar->availableSlots()->equals($sevenSlots->leftoverAfterRemovingCommonWith($minimumSlot)));
    }

    #[Test]
    public function loadsCalendarForMultipleResources(): void
    {
        // given
        $resourceId = ResourceId::newOne();
        $resourceId2 = ResourceId::newOne();
        $durationOfSevenSlots = Duration::ofMinutes(7 * Segments::DEFAULT_SEGMENT_DURATION_IN_MINUTES);
        $sevenSlots = TimeSlot::createTimeSlotAtUTCOfDuration(2021, 1, 1, $durationOfSevenSlots);
        $minimumSlot = new TimeSlot($sevenSlots->from, $sevenSlots->from->modify(sprintf('+%s minutes', Segments::DEFAULT_SEGMENT_DURATION_IN_MINUTES)));

        $owner = Owner::newOne();
        $this->availabilityFacade->createResourceSlots($resourceId, $sevenSlots);
        $this->availabilityFacade->createResourceSlots($resourceId2, $sevenSlots);

        // when
        $this->availabilityFacade->block($resourceId, $minimumSlot, $owner);
        $this->availabilityFacade->block($resourceId2, $minimumSlot, $owner);

        // then
        $calendars = $this->availabilityFacade->loadCalendars(Set::of($resourceId, $resourceId2), $sevenSlots);
        self::assertTrue($calendars->get($resourceId)->takenBy($owner)->equals(GenericList::of($minimumSlot)));
        self::assertTrue($calendars->get($resourceId2)->takenBy($owner)->equals(GenericList::of($minimumSlot)));
        self::assertTrue($calendars->get($resourceId)->availableSlots()->equals($sevenSlots->leftoverAfterRemovingCommonWith($minimumSlot)));
        self::assertTrue($calendars->get($resourceId2)->availableSlots()->equals($sevenSlots->leftoverAfterRemovingCommonWith($minimumSlot)));
    }
}
