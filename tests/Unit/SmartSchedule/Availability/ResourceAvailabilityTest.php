<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailability;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailabilityId;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceAvailability::class)]
final class ResourceAvailabilityTest extends TestCase
{
    private ResourceAvailabilityId $resourceAvailabilityId;
    private Owner $ownerOne;
    private Owner $ownerTwo;

    protected function setUp(): void
    {
        $this->resourceAvailabilityId = ResourceAvailabilityId::newOne();
        $this->ownerOne = Owner::newOne();
        $this->ownerTwo = Owner::newOne();
    }

    #[Test]
    public function canBeBlockedWhenIsAvailable(): void
    {
        // given
        $resourceAvailability = $this->resourceAvailability();

        // when
        $result = $resourceAvailability->block($this->ownerOne);

        // then
        self::assertTrue($result);
    }

    #[Test]
    public function cantBeBlockedWhenAlreadyBlockedBySomeoneElse(): void
    {
        // given
        $resourceAvailability = $this->resourceAvailability();
        // and
        $resourceAvailability->block($this->ownerOne);

        // when
        $result = $resourceAvailability->block($this->ownerTwo);

        // then
        self::assertFalse($result);
    }

    #[Test]
    public function canBeReleasedOnlyByInitialOwner(): void
    {
        // given
        $resourceAvailability = $this->resourceAvailability();
        // and
        $resourceAvailability->block($this->ownerOne);

        // when
        $result = $resourceAvailability->release($this->ownerOne);

        // then
        self::assertTrue($result);
    }

    #[Test]
    public function cantBeReleaseBySomeoneElse(): void
    {
        // given
        $resourceAvailability = $this->resourceAvailability();
        // and
        $resourceAvailability->block($this->ownerOne);

        // when
        $result = $resourceAvailability->release($this->ownerTwo);

        // then
        self::assertFalse($result);
    }

    #[Test]
    public function canBeBlockedBySomeoneElseAfterReleasing(): void
    {
        // given
        $resourceAvailability = $this->resourceAvailability();
        // and
        $resourceAvailability->block($this->ownerOne);
        // and
        $resourceAvailability->release($this->ownerOne);

        // when
        $result = $resourceAvailability->block($this->ownerTwo);

        // then
        self::assertTrue($result);
    }

    #[Test]
    public function canDisableWhenAvailable(): void
    {
        // given
        $resourceAvailability = $this->resourceAvailability();

        // when
        $result = $resourceAvailability->disable($this->ownerOne);

        // then
        self::assertTrue($result);
        self::assertTrue($resourceAvailability->isDisabled());
        self::assertTrue($resourceAvailability->isDisabledBy($this->ownerOne));
    }

    #[Test]
    public function canDisableWhenBlocked(): void
    {
        // given
        $resourceAvailability = $this->resourceAvailability();
        // and
        $resultBlocking = $resourceAvailability->block($this->ownerOne);

        // when
        $resultDisabling = $resourceAvailability->disable($this->ownerTwo);

        // then
        self::assertTrue($resultBlocking);
        self::assertTrue($resultDisabling);
        self::assertTrue($resourceAvailability->isDisabled());
        self::assertTrue($resourceAvailability->isDisabledBy($this->ownerTwo));
    }

    #[Test]
    public function cantBeBlockedWhileDisabled(): void
    {
        // given
        $resourceAvailability = $this->resourceAvailability();
        // and
        $resultDisabling = $resourceAvailability->disable($this->ownerOne);

        // when
        $resultBlocking = $resourceAvailability->block($this->ownerTwo);
        $resultBlockingBySameOwner = $resourceAvailability->block($this->ownerOne);

        // then
        self::assertTrue($resultDisabling);
        self::assertFalse($resultBlocking);
        self::assertFalse($resultBlockingBySameOwner);
        self::assertTrue($resourceAvailability->isDisabled());
        self::assertTrue($resourceAvailability->isDisabledBy($this->ownerOne));
    }

    #[Test]
    public function canBeEnabledByInitialRequester(): void
    {
        // given
        $resourceAvailability = $this->resourceAvailability();
        // and
        $resourceAvailability->disable($this->ownerOne);

        // when
        $result = $resourceAvailability->enable($this->ownerOne);

        // then
        self::assertTrue($result);
        self::assertFalse($resourceAvailability->isDisabled());
        self::assertFalse($resourceAvailability->isDisabledBy($this->ownerOne));
    }

    #[Test]
    public function cantBeEnabledByAnotherRequester(): void
    {
        // given
        $resourceAvailability = $this->resourceAvailability();
        // and
        $resourceAvailability->disable($this->ownerOne);

        // when
        $result = $resourceAvailability->enable($this->ownerTwo);

        // then
        self::assertFalse($result);
        self::assertTrue($resourceAvailability->isDisabled());
        self::assertTrue($resourceAvailability->isDisabledBy($this->ownerOne));
    }

    #[Test]
    public function canBeBlockedAgainAfterEnabling(): void
    {
        // given
        $resourceAvailability = $this->resourceAvailability();
        // and
        $resourceAvailability->disable($this->ownerOne);
        // and
        $resourceAvailability->enable($this->ownerOne);

        // when
        $result = $resourceAvailability->block($this->ownerTwo);

        // then
        self::assertTrue($result);
    }

    private function resourceAvailability(): ResourceAvailability
    {
        return ResourceAvailability::of($this->resourceAvailabilityId, ResourceId::newOne(), TimeSlot::createDailyTimeSlotAtUTC(2000, 1, 1));
    }
}
