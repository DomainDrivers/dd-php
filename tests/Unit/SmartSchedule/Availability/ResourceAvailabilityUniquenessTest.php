<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Availability;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use DomainDrivers\SmartSchedule\Availability\Infrastructure\DbalResourceAvailabilityRepository;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailability;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailabilityId;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\Tests\Phpunit\AssertThrows;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(DbalResourceAvailabilityRepository::class)]
final class ResourceAvailabilityUniquenessTest extends KernelTestCase
{
    use AssertThrows;

    private DbalResourceAvailabilityRepository $repository;

    protected function setUp(): void
    {
        $this->repository = self::getContainer()->get(DbalResourceAvailabilityRepository::class);
    }

    #[Test]
    public function cantSaveTwoAvailabilitiesWithSameResourceIdAndSegment(): void
    {
        // given
        $resourceId = ResourceAvailabilityId::newOne();
        $anotherResourceId = ResourceAvailabilityId::newOne();
        $resourceAvailabilityId = ResourceAvailabilityId::newOne();

        // when
        $this->repository->saveNew(ResourceAvailability::of($resourceAvailabilityId, $resourceId, TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1)));

        // expect
        self::assertThrows(UniqueConstraintViolationException::class, fn () => $this->repository->saveNew(ResourceAvailability::of($resourceAvailabilityId, $anotherResourceId, TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1))));
    }
}
