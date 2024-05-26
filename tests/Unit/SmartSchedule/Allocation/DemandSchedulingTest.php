<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\AllocationFacade;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[CoversClass(AllocationFacade::class)]
final class DemandSchedulingTest extends KernelTestCase
{
    use InteractsWithMessenger;

    private AllocationFacade $allocationFacade;

    #[\Override]
    protected function setUp(): void
    {
        $this->allocationFacade = self::getContainer()->get(AllocationFacade::class);
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
