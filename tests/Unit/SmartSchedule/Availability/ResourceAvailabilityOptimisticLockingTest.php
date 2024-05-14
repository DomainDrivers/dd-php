<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Availability\Infrastructure\DbalResourceAvailabilityRepository;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailability;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailabilityId;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\Tests\Phpunit\AssertThrows;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(DbalResourceAvailabilityRepository::class)]
final class ResourceAvailabilityOptimisticLockingTest extends KernelTestCase
{
    use AssertThrows;

    private DbalResourceAvailabilityRepository $repository;

    protected function setUp(): void
    {
        $this->repository = self::getContainer()->get(DbalResourceAvailabilityRepository::class);
    }

    #[Test]
    public function updateBumpsVersion(): void
    {
        // given
        $resourceAvailabilityId = ResourceAvailabilityId::newOne();
        $resourceId = ResourceAvailabilityId::newOne();
        $resourceAvailability = ResourceAvailability::of($resourceAvailabilityId, $resourceId, TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1));
        $this->repository->saveNew($resourceAvailability);

        // when
        $resourceAvailability = $this->repository->loadById($resourceAvailabilityId) ?? throw new \RuntimeException('not possible');
        $resourceAvailability->block(Owner::newOne());
        $this->repository->saveCheckingVersion($resourceAvailability);

        // then
        self::assertSame(1, $this->repository->loadById($resourceAvailabilityId)->version());
    }

    #[Test]
    public function cantUpdateConcurrently(): void
    {
        // given
        $resourceAvailabilityId = ResourceAvailabilityId::newOne();
        $resourceId = ResourceAvailabilityId::newOne();
        $this->repository->saveNew(ResourceAvailability::of($resourceAvailabilityId, $resourceId, TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1)));

        // when
        $resourceAvailability1 = $this->repository->loadById($resourceAvailabilityId) ?? throw new \RuntimeException('not possible');
        $resourceAvailability1->block(Owner::newOne());

        $resourceAvailability2 = $this->repository->loadById($resourceAvailabilityId);
        $resourceAvailability2->block(Owner::newOne());

        $result2 = $this->repository->saveCheckingVersion($resourceAvailability2);
        $result1 = $this->repository->saveCheckingVersion($resourceAvailability1);

        // then
        self::assertTrue($result2);
        self::assertFalse($result1);
        self::assertSame(1, $this->repository->loadById($resourceAvailabilityId)->version());
    }
}
