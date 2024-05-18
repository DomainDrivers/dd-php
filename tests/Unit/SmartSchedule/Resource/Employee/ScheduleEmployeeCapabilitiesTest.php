<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Resource\Employee;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityFinder;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeFacade;
use DomainDrivers\SmartSchedule\Resource\Employee\ScheduleEmployeeCapabilities;
use DomainDrivers\SmartSchedule\Resource\Employee\Seniority;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(ScheduleEmployeeCapabilities::class)]
final class ScheduleEmployeeCapabilitiesTest extends KernelTestCase
{
    private EmployeeFacade $employeeFacade;
    private CapabilityFinder $capabilityFinder;

    protected function setUp(): void
    {
        $this->employeeFacade = self::getContainer()->get(EmployeeFacade::class);
        $this->capabilityFinder = self::getContainer()->get(CapabilityFinder::class);
    }

    #[Test]
    public function canSetupCapabilitiesAccordingToPolicy(): void
    {
        // given
        $employeeId = $this->employeeFacade->addEmployee('resourceName', 'lastName', Seniority::LEAD, Capability::skills('php', 'python'), Capability::permissions('admin'));

        // when
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $allocations = $this->employeeFacade->scheduleCapabilities($employeeId, $oneDay);

        // then
        $loaded = $this->capabilityFinder->findById($allocations);
        self::assertSame($allocations->length(), $loaded->all->length());
    }
}
