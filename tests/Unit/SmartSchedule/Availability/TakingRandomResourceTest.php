<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailability;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(AvailabilityFacade::class)]
final class TakingRandomResourceTest extends KernelTestCase
{
    private AvailabilityFacade $availabilityFacade;

    protected function setUp(): void
    {
        $this->availabilityFacade = self::getContainer()->get(AvailabilityFacade::class);
    }

    #[Test]
    public function canTakeRandomResourceFromPool(): void
    {
        // given
        $resourceId1 = ResourceId::newOne();
        $resourceId2 = ResourceId::newOne();
        $resourceId3 = ResourceId::newOne();
        $resourcesPool = Set::of($resourceId1, $resourceId2, $resourceId3);
        // and
        $owner1 = Owner::newOne();
        $owner2 = Owner::newOne();
        $owner3 = Owner::newOne();
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);

        // and
        $this->availabilityFacade->createResourceSlots($resourceId1, $oneDay);
        $this->availabilityFacade->createResourceSlots($resourceId2, $oneDay);
        $this->availabilityFacade->createResourceSlots($resourceId3, $oneDay);

        // when
        $taken1 = $this->availabilityFacade->blockRandomAvailable($resourcesPool, $oneDay, $owner1)->get();

        // then
        self::assertTrue($resourcesPool->contains($taken1));
        $this->assertThatResourceIsTakeByOwner($taken1, $owner1, $oneDay);

        // when
        $taken2 = $this->availabilityFacade->blockRandomAvailable($resourcesPool, $oneDay, $owner2)->get();

        // then
        self::assertTrue($resourcesPool->contains($taken2));
        $this->assertThatResourceIsTakeByOwner($taken2, $owner2, $oneDay);

        // when
        $taken3 = $this->availabilityFacade->blockRandomAvailable($resourcesPool, $oneDay, $owner3)->get();

        // then
        self::assertTrue($resourcesPool->contains($taken3));
        $this->assertThatResourceIsTakeByOwner($taken3, $owner3, $oneDay);

        // when
        $taken4 = $this->availabilityFacade->blockRandomAvailable($resourcesPool, $oneDay, $owner3);

        // then
        self::assertTrue($taken4->isEmpty());
    }

    #[Test]
    public function nothingIsTakenWhenNoResourceInPool(): void
    {
        // given
        $resources = Set::of(ResourceId::newOne(), ResourceId::newOne(), ResourceId::newOne());

        // when
        $jan1 = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $taken1 = $this->availabilityFacade->blockRandomAvailable($resources, $jan1, Owner::newOne());

        // then
        self::assertTrue($taken1->isEmpty());
    }

    private function assertThatResourceIsTakeByOwner(ResourceId $resourceId, Owner $owner, TimeSlot $timeSlot): void
    {
        $resourceAvailability = $this->availabilityFacade->find($resourceId, $timeSlot);
        self::assertTrue($resourceAvailability->resourceAvailabilities->allMatch(fn (ResourceAvailability $ra) => $ra->blockedBy()->equals($owner)));
    }
}
