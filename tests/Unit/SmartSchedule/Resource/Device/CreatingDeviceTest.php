<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Resource\Device;

use DomainDrivers\SmartSchedule\Resource\Device\DeviceFacade;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\GenericList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(DeviceFacade::class)]
final class CreatingDeviceTest extends KernelTestCase
{
    private DeviceFacade $deviceFacade;

    protected function setUp(): void
    {
        $this->deviceFacade = self::getContainer()->get(DeviceFacade::class);
    }

    #[Test]
    public function canCreateAndLoadDevices(): void
    {
        // given
        $device = $this->deviceFacade->createDevice('super-excavator-1000', Capability::assets('BULLDOZER', 'EXCAVATOR'));

        // when
        $loaded = $this->deviceFacade->findDevice($device);

        // then
        self::assertTrue($loaded->assets->equals(Capability::assets('BULLDOZER', 'EXCAVATOR')));
        self::assertSame('super-excavator-1000', $loaded->model);
    }

    #[Test]
    public function canFindAllCapabilities(): void
    {
        // given
        $this->deviceFacade->createDevice('super-excavator-1000', Capability::assets('SMALL-EXCAVATOR', 'BULLDOZER'));
        $this->deviceFacade->createDevice('super-excavator-2000', Capability::assets('MEDIUM-EXCAVATOR', 'UBER-BULLDOZER'));
        $this->deviceFacade->createDevice('super-excavator-3000', Capability::assets('BIG-EXCAVATOR'));

        // when
        $loaded = $this->deviceFacade->findAllCapabilities();

        // then
        self::assertTrue($loaded->containsAll(GenericList::of(
            Capability::asset('SMALL-EXCAVATOR'),
            Capability::asset('BULLDOZER'),
            Capability::asset('MEDIUM-EXCAVATOR'),
            Capability::asset('UBER-BULLDOZER'),
            Capability::asset('BIG-EXCAVATOR')
        )));
    }
}
