<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Availability\Infrastructure\DbalResourceAvailabilityRepository;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailability;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailabilityId;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(DbalResourceAvailabilityRepository::class)]
final class ResourceAvailabilityLoadingTest extends KernelTestCase
{
    private DbalResourceAvailabilityRepository $repository;

    protected function setUp(): void
    {
        $this->repository = self::getContainer()->get(DbalResourceAvailabilityRepository::class);
    }

    #[Test]
    public function canSaveAndLoadById(): void
    {
        // given
        $resourceAvailabilityId = ResourceAvailabilityId::newOne();
        $resourceId = ResourceAvailabilityId::newOne();
        $resourceAvailability = ResourceAvailability::of($resourceAvailabilityId, $resourceId, TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1));

        // when
        $this->repository->saveNew($resourceAvailability);

        // then
        $loaded = $this->repository->loadById($resourceAvailabilityId);
        self::assertNotNull($loaded);
        self::assertEquals($resourceAvailability->segment, $loaded->segment);
        self::assertEquals($resourceAvailability->resourceId, $loaded->resourceId);
        self::assertEquals($resourceAvailability->blockedBy(), $loaded->blockedBy());
    }
}
