<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\AllocationFacade;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityFinder;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\EventsPublisher;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\NativeClock;

#[CoversClass(AllocationFacade::class)]
final class DemandSchedulingTest extends TestCase
{
    private AllocationFacade $allocationFacade;

    #[\Override]
    protected function setUp(): void
    {
        $this->allocationFacade = new AllocationFacade(
            new InMemoryProjectAllocationsRepository(),
            $this->createMock(AvailabilityFacade::class),
            $this->createMock(CapabilityFinder::class),
            $this->createMock(EventsPublisher::class),
            new NativeClock()
        );
    }

    #[Test]
    public function canScheduleProjectDemands(): void
    {
        // given
        $projectId = ProjectAllocationsId::newOne();
        $php = new Demand(Capability::skill('php'), TimeSlot::createDailyTimeSlotAtUTC(2022, 2, 2));

        // when
        $this->allocationFacade->scheduleProjectAllocationDemands($projectId, Demands::of($php));

        // then
        $summary = $this->allocationFacade->findAllProjectsAllocations();
        self::assertTrue($summary->projectAllocations->containsKey($projectId->toString()));
        self::assertTrue($summary->projectAllocations->get($projectId->toString())->get()->all->isEmpty());
        self::assertTrue($summary->demands->get($projectId->toString())->get()->all->equals(GenericList::of($php)));
    }
}
