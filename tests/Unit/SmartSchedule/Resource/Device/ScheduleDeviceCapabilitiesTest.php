<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Resource\Device;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityFinder;
use DomainDrivers\SmartSchedule\Resource\Device\DeviceFacade;
use DomainDrivers\SmartSchedule\Resource\Device\ScheduleDeviceCapabilities;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(ScheduleDeviceCapabilities::class)]
final class ScheduleDeviceCapabilitiesTest extends KernelTestCase
{
    private DeviceFacade $deviceFacade;
    private CapabilityFinder $capabilityFinder;

    protected function setUp(): void
    {
        $this->deviceFacade = self::getContainer()->get(DeviceFacade::class);
        $this->capabilityFinder = self::getContainer()->get(CapabilityFinder::class);
    }

    #[Test]
    public function canSetupCapabilitiesAccordingToPolicy(): void
    {
        // given
        $deviceId = $this->deviceFacade->createDevice('super-bulldozer-3000', Capability::assets('EXCAVATOR', 'BULLDOZER'));

        // when
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $allocations = $this->deviceFacade->scheduleCapabilities($deviceId, $oneDay);

        // then
        $loaded = $this->capabilityFinder->findById($allocations);
        self::assertSame($allocations->length(), $loaded->all->length());
    }
}
